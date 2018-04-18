<?php
/**
 * Created by PhpStorm.
 * User: hurace
 * Date: 2018/4/18 0018
 * Time: 11:38
 */

/**
 * 树形结构操作模型:改进的前序遍历 —— 表必备字段: id,name,lft,rgt,nlevel
 */
class TreeModel extends Model {

    protected $_validate = array(
        array('name', '2,32', '字符长度需在2到32字符之内', Model::MUST_VALIDATE, 'length'),
    );

    /**
     * 判断表中是否有根节点
     * @return int
     */
    function isHasRoot() {
        return $this->where('lft=1 and nlevel=0')->getField('id');
    }

    /**
     * 判断名字是否已存在
     * @param string $name
     * @return int
     */
    function isExistName($name) {
        return $this->where(array('name' => $name))->getField('id');
    }

    /**
     * 判断节点是否为指定节点的子节点
     * @param int $childId : 子节点
     * @param int $parentId : 父节点
     * @return bool
     */
    function isChild($childId, $parentId) {
        if ($childId == $parentId) {
            return false;
        }

        $nodes = $this->whereNode(array($childId, $parentId))->getField('id,lft,rgt');
        $child = $nodes[$childId];
        $parent = $nodes[$parentId];

        if ($child && $parent) {
            return ($child['lft'] > $parent['lft']) && ($child['rgt'] < $parent['rgt']);
        }

        return false;
    }

    /**
     * 获取节点信息
     * @param int $nodeId
     * @return array
     */
    function getNodeInfo($nodeId) {
        return $this->whereNode($nodeId)->field('id,lft,rgt,nlevel')->find();
    }

    //---------------------------------where条件 代替where()----------------------------------------

    /**
     * 条件：节点
     * @param int|array $nodeId
     * @param array $conditions : 附加条件
     * @return this
     */
    function whereNode($nodeId, array $conditions = array()) {
        $nodeId = (array)$nodeId;
        $conditions['id'] = array('in', $nodeId);
        return $this->where($conditions);
    }

    /**
     * 条件：获取节点（在指定的层级范围内）的子节点
     * @param array $node
     * @param int $childLevel : 距当前节点的层级, 为0时获取所有
     * @param array $conditions : 附加条件
     * @param bool $onlyLeaf : 为true时只获取叶子节点
     * @return this
     */
    function whereChild(array $node, $childLevel = 1, array $conditions = array(), $onlyLeaf = false) {
        $conditions['lft'] = array('gt', $node['lft']);
        $conditions['rgt'][] = array('lt', $node['rgt']);

        if ($onlyLeaf) {
            $conditions['rgt'][] = array('eq', 'lft+1');
        }

        if ($childLevel > 0) {
            $conditions['nlevel'] = array(array('gt', $node['nlevel']), array('elt', $node['nlevel'] + $childLevel));
        }

        return $this->where($conditions);
    }


    /**
     * 条件：获取节点（在指定的层级范围内）的(直系)祖先节点
     * @param array $node : 节点信息
     * @param int $parentLevel :距当前节点的层级，0为获取所有
     * @param array $conditions : 附加条件
     * @return array
     */
    function whereParent(array $node, $parentLevel = 1, array $conditions = array()) {
        //lft<$lft and rgt>$rgt
        $conditions['lft'] = array('lt', $node['lft']);
        $conditions['rgt'] = array('gt', $node['rgt']);

        if ($parentLevel > 0) {
            $conditions['nlevel'] = array(array('lt', $node['nlevel']), array('egt', $node['nlevel'] - $parentLevel));
        }

        return $this->where($conditions);
    }

    /**
     * 条件：获取节点排除指定节点后的子孙节点(即从node的子孙节点中将out节点及其子节点排除)
     * @param array $node :
     * @param array $out : 排除的节点
     * @param array $conditions : 附加条件
     * @return this
     */
    function whereChildWithout(array $node, array $out, array $conditions = array()) {
        $conditions['lft'] = array('gt', $node['lft']);
        $conditions['rgt'] = array('lt', $node['rgt']);
        $conditions['_string'] = 'lft<' . $out['lft'] . ' or rgt>' . $out['rgt'];

        return $this->where($conditions);
    }


    //------------------------------------append----------------------------------------

    /**
     * 添加节点
     * @param array $data : 要添加的节点信息
     * @param int $noteId : 节点id, 为0时添加首节点
     * @param bool $insertToRight : 添加在目标节点的最右,为false时添加在最左
     * @return int
     */
    function appendNode(array $data, $nodeId, $insertToRight = true) {
        if (!$this->create($data)) {
            return false;
        }

        if (!$nodeId) {
            return $this->appendRootNode($data);
        }

        $node = $this->getNodeInfo($nodeId);

        if ($node) {
            $this->startTrans();

            if ($insertToRight) {
                $id = $this->appendRightNode($data, $node);
            } else {
                $id = $this->appendLeftNode($data, $node);
            }

            $id !== false ? $this->commit() : $this->rollback();

            return $id;
        }

        return false;
    }

    /**
     * 添加根节点
     * @param array $data : 要添加的节点信息
     * @return int
     */
    protected function appendRootNode(array $data) {
        if (!$this->isHasRoot()) {
            $data['lft'] = 1;
            $data['rgt'] = 2;
            $data['nlevel'] = 0;

            return $this->add($data);
        }

        return false;
    }

    /**
     * 添加左节点: 添加在目标节点的最左
     * @param array $data : 要添加的节点信息
     * @param array $node : 目标节点信息
     * @return int
     */
    protected function appendLeftNode(array $data, $node) {
        //所有左(右)值大于目标节点的左值的节点其右值+2(包含当前节点的子节点)
        $upLft = $this->where('lft>' . $node['lft'])->setInc('lft', 2);
        $upRgt = $this->where('rgt>' . $node['lft'])->setInc('rgt', 2);

        $data['lft'] = $node['lft'] + 1; //左值加1
        $data['rgt'] = $node['lft'] + 2;
        $data['nlevel'] = $node['nlevel'] + 1;

        $id = $this->add($data);

        if ($upLft !== false && $upRgt && $id) {  //可能无左节点的更新,但肯定有右节点的更新
            return $id;
        } else {
            return false;
        }
    }

    /**
     * 添加右节点: 添加在目标节点的最右
     * @param array $data : 要添加的节点信息
     * @param array $node : 目标节点信息
     * @return int
     */
    protected function appendRightNode(array $data, $node) {
        //所有左值大于目标节点的右值的节点其右值+2(不包含当前节点的子节点)
        $upLft = $this->where('lft>' . $node['rgt'])->setInc('lft', 2); //左值全部+2
        $upRgt = $this->where('rgt>=' . $node['rgt'])->setInc('rgt', 2); //含当前节点的右值

        $data['lft'] = $node['rgt']; //当前节点右值
        $data['rgt'] = $node['rgt'] + 1;
        $data['nlevel'] = $node['nlevel'] + 1;

        $id = $this->add($data);

        if ($upLft !== false && $upRgt && $id) {
            return $id;
        } else {
            return false;
        }
    }


    //-----------------------------------remove-----------------------------------------

    /**
     * 删除节点
     * @param int $nodeId
     * @param bool $isDelChild : 是否一起删除该节点下的子节点, 为false时节点下的子节点将置于该节点的父节点下
     * @return bool
     */
    function removeNode($nodeId, $isDelChilds = true) {
        $nodeInfo = $this->getNodeInfo($nodeId);

        if ($nodeInfo && $nodeInfo['nlevel']) {   //排除根节点
            $this->startTrans();

            if ($isDelChilds) {
                $del = $this->removeChildNodes($nodeInfo);
            } else {
                $del = $this->removeSingleNode($nodeInfo);
            }

            $del !== false ? $this->commit() : $this->rollback();

            return $del;
        }

        return false;
    }

    /**
     * 删除单一节点,若此节点含有子节点,则将子节点转到此节点的父节点下
     * @param array $nodeInfo :待删除的节点信息
     * @return bool
     */
    protected function removeSingleNode(array $nodeInfo) {
        //删除节点
        $del = $this->where('id=' . $nodeInfo['id'])->delete();

        //更新节点的子节点：左右值都减1，层级减1
        $sets = array(
            'rgt' => array('exp', 'rgt - 1'),
            'lft' => array('exp', 'lft - 1'),
            'nlevel' => array('exp', 'nlevel - 1'),
        );
        $upChild = $this->where('lft between ' . $nodeInfo['lft'] . ' and ' . $nodeInfo['rgt'])->setField($sets);

        //更新右值
        $upRgt = $this->where('rgt>' . $nodeInfo['rgt'])->setDec('rgt', 2);

        //更新左值
        $upLft = $this->where('lft>' . $nodeInfo['rgt'])->setDec('lft', 2);

        return $del && $upRgt && $upLft !== false && $upChild !== false;
    }

    /**
     * 删除节点及其子节点
     * @param array $nodeInfo :待删除的节点信息
     * @param bool
     */
    protected function removeChildNodes(array $nodeInfo) {
        //删除节点及其子节点
        $del = $this->where('lft between ' . $nodeInfo['lft'] . ' and ' . $nodeInfo['rgt'])->delete();

        $diff = $nodeInfo['rgt'] - $nodeInfo['lft'] + 1;    //节点差值

        //更新右值
        $upRgt = $this->where('rgt>' . $nodeInfo['rgt'])->setDec('rgt', $diff);

        //更新左值
        $upLft = $this->where('lft>' . $nodeInfo['rgt'])->setDec('lft', $diff);

        return $del && $upRgt && $upLft !== false;
    }


    //-----------------------------------move-------------------------------------------

    /**
     * 节点移动:移动节点时该节点的子节点也会一起移动(禁止从父节点移动到子节点)
     * @param int $fromId : 待移动的节点
     * @param int $toId : 移向的目的节点
     * @return bool
     */
    function moveTo($fromId, $toId) {
        if ($fromId == $toId) {   //移到自身
            $this->error = '父节点错误';
            return false;
        }

        $nodes = $this->whereNode(array($fromId, $toId))->getField('id,lft,rgt,nlevel');
        $from = $nodes[$fromId];
        $to = $nodes[$toId];

        if (!($from && $to)) {
            $this->error = '未知节点';
            return false;

        } else if ($from['lft'] < $to['lft'] && $from['rgt'] > $to['rgt']) {    //从父节点移到子节点
            $this->error = '禁止从父节点移向子节点';
            return false;

        } else if ($from['nlevel'] == $to['nlevel'] + 1 && $to['lft'] < $from['lft'] && $to['rgt'] > $from['rgt']) { //本身即为父子节点
            return true;
        }

        if ($from['lft'] > $to['rgt']) {   //左移
            $move = $this->moveToLeft($from, $to);

        } else if ($from['rgt'] < $to['lft']) {    //右移
            $move = $this->moveToRight($from, $to);

        } else {
            $move = $this->moveToParent($from, $to); //移向父节点
        }

        $move !== false ? $this->commit() : $this->rollback();

        return $move;
    }

    /**
     * 节点左移
     * @param array $src : 要移动的节点信息
     * @param array $to : 目标节点信息
     * @return bool
     */
    protected function moveToLeft(array $src, array $to) {
        $nodeStep = $src['rgt'] - $src['lft'] + 1;

        //-------------置于目标节点的最右--------------------

        //更新中间节点的右值,此时中间节点的右值与待移节点的右值有重复
        $upRgt = $this->where('rgt<' . $src['lft'] . ' and rgt>=' . $to['rgt'])->setInc('rgt', $nodeStep);    //包含目标节点

        //更新移动节点的左右值(判断待移节点的左值), 此时与中间节点的左值有重复,右值无重复
        $moveStep = $src['lft'] - $to['rgt'];
        $diffLevel = $to['nlevel'] - $src['nlevel'] + 1; //比目标等级低1

        $sets = array(
            'lft' => array('exp', 'lft-' . $moveStep),
            'rgt' => array('exp', 'rgt-' . $moveStep),
            'nlevel' => array('exp', 'nlevel+' . $diffLevel),
        );
        $upNode = $this->where('lft between ' . $src['lft'] . ' and ' . $src['rgt'])->setField($sets);

        //更新中间节点的左值(判断中间节点的右值),$to['rgt']+$nodeStep为移动后的目标节点的右值
        $wheres = 'lft<' . $src['lft'] . ' and lft>' . $to['lft'] . ' and rgt>' . ($to['rgt'] + $nodeStep); //左值更新范围(排序移动节点)

        $upLft = $this->where($wheres)->setInc('lft', $nodeStep);

        return $upNode && $upRgt && $upLft !== false;
    }

    /**
     * 节点右移
     * @param array $from : 要移动的节点信息
     * @param array $to : 目标节点信息
     * @return bool
     */
    protected function moveToRight(array $from, array $to) {
        $nodeStep = $from['rgt'] - $from['lft'] + 1;

        //-----------------置于目标节点的最左-----------------

        //更新中间节点的左值,此时中间节点的左值与待移节点的左值有重复
        $upLft = $this->where('lft>' . $from['rgt'] . ' and lft<=' . $to['lft'])->setDec('lft', $nodeStep);   //包含目标节点

        //更新移动节点的左右值(判断待移节点的右值), 此时与中间节点的右值有重复,左值无重复
        $moveStep = $to['lft'] - $from['rgt'];
        $diffLevel = $to['nlevel'] - $from['nlevel'] + 1; //比目标等级低1

        $sets = array(
            'lft' => array('exp', 'lft+' . $moveStep),
            'rgt' => array('exp', 'rgt+' . $moveStep),
            'nlevel' => array('exp', 'nlevel+' . $diffLevel),
        );

        $upNode = $this->where('rgt between ' . $from['lft'] . ' and ' . $from['rgt'])->setField($sets);

        //更新中间节点的右值(判断中间节点的左值),$to['lft']-$nodeStep为移动后的目标节点的左值
        $wheres = 'rgt>' . $from['rgt'] . ' and rgt<' . $to['rgt'] . ' and lft<' . ($to['lft'] - $nodeStep);   //右值更新范围(排除移动节点)

        $upRgt = $this->where($wheres)->setDec('rgt', $nodeStep);

        return $upNode && $upRgt && $upLft !== false;
    }

    /**
     * 节点上移
     * @param array $from : 要移动的节点信息
     * @param array $to : 目标节点信息
     * @return bool
     */
    protected function moveToParent(array $from, array $to) {
        $nodeStep = $from['rgt'] - $from['lft'] + 1;

        //-----------------置于目标节点的最右-----------------

        //更新中间节点的右值,此时中间节点的右值与待移节点的右值有重复
        $upLft = $this->where('rgt>' . $from['rgt'] . ' and rgt<' . $to['rgt'])->setDec('rgt', $nodeStep);    //不包含目标节点

        //更新移动节点的左右值(判断待移节点的右值), 此时与中间节点的左值有重复,右值无重复
        $moveStep = $to['rgt'] - $from['rgt'] - 1;  //本身就在$to节点下,故再减1
        $diffLevel = $to['nlevel'] - $from['nlevel'] + 1; //比目标等级低1

        $sets = array(
            'lft' => array('exp', 'lft+' . $moveStep),
            'rgt' => array('exp', 'rgt+' . $moveStep),
            'nlevel' => array('exp', 'nlevel+' . $diffLevel),
        );

        $upNode = $this->where('lft between ' . $from['lft'] . ' and ' . $from['rgt'])->setField($sets);

        //更新中间节点的左值(判断中间节点的右值),$to['rgt']-$nodeStep为排除移动节点
        $wheres = 'lft>' . $from['lft'] . ' and rgt>' . $from['lft'] . ' and rgt<' . ($to['rgt'] - $nodeStep); //左值更新范围(排除移动节点)

        $upRgt = $this->where($wheres)->setDec('lft', $nodeStep);

        return $upNode && $upRgt && $upLft !== false;
    }

}