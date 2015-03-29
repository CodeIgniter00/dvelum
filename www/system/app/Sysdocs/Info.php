<?php
class Sysdocs_Info
{
    /**
     * Get class info by HID
     * @param string $fileHid
     * @param integer $version
     * @return array
     */
    public function getClassInfoByFileHid($fileHid , $language , $version)
    {
        $classId = Model::factory('sysdocs_class')->getList(array('limit'=>1) , array('fileHid'=>$fileHid,'vers'=>$version) , array('id'));
        if(empty($classId))
            return array();

        return $this->getClassInfo($classId[0]['id'] , $language);
    }
    /**
     * Find previous localization
     * @param string $objectClass
     * @param string $field
     * @param string $language
     * @return string | false
     */
    public function findLocale($objectClass , $field , $language , $hid)
    {
        $data = Model::factory('sysdocs_localization')->getList(
            array('start'=>0,'limit'=>1,'sort'=>'vers','dir'=>'DESC'),
            array(
                'lang'=>$language,
                'object_class'=>$objectClass,
                'hid'=>$hid,
                'field'=>$field
            ),
            array('value')
        );
    
        if(empty($data))
            return false;
    
        return $data[0]['value'];
    }
    /**
     * Get class info by id
     * @param integer $id
     * @param string $lanuage
     * @return array
     */
    protected function getClassInfo($id , $language)
    {
        $classModel = Model::factory('sysdocs_class');
        $info =  $classModel->getItem($id);

        if(empty($info))
            return array();
      
        $desc = $this->findLocale('sysdocs_class' , 'description' , $language, $info['hid']);
        if(empty($desc)){
          $desc = nl2br($info['description']);
        }
        
        $result = array(
                        'object_id'=>$info['id'],
                        'hid'=>$info['hid'],
        				'name' => $info['name'],
        		        'extends' => $info['extends'],
        		        'itemType'=> $info['itemType'],
        		        'abstract' => $info['abstract'],
        		        'deprecated' => $info['deprecated'],
                        'implements' => $info['implements'],
                        'hierarchy' => array(),
        		        'description' => $desc,
                        'properties' => $this->getClassProperties($id, $language),
                        'methods' => $this->getClassMethods($id , $language)
        );

        if(!empty($info['parentId']))
        {
          $tree = $classModel->getTree($info['vers']);
          $parentList  = $tree->getParentsList($id);
          $parentList[] = $id;


          if(!empty($parentList))
          {
            $classObject = new stdClass();
            $classObject->id = $info['id'];
            $classObject->text = $info['name'];
            //$classObject->leaf = true;
            $classObject->expanded = true;
            $classObject->iconCls = 'emptyIcon';

            foreach ($parentList as  $k=>$id)
            {
              $cdata = $tree->getItem($id);

              $object = new stdClass();
              $object->id = $cdata['id'];
              $object->text = $cdata['data'];
             // $object->leaf = true;
              $object->expanded = true;
              $object->iconCls = 'emptyIcon';
              $parentList[$k] = $object;
            }

            $base = false;
            $curObject = false;
            foreach ($parentList as  $item)
            {
            	if(!$base){
            		$base = $item;
            		$curObject = $base;
            		continue;
            	}
            	$tmp = $curObject;
            	$tmp->children = $item;
            	$curObject = $item;
            }
          }
          $result['hierarchy'] = $base;
        }
        return $result;
    }

    /**
     * Get class properties info by class id
     * @param integer $classId
     * @return array
     */
    public function getClassProperties($classId , $language)
    {
      $propModel = Model::factory('sysdocs_class_property');
      $list = $propModel->getList(
          array('sort'=>'name','dir'=>'ASC') ,
          array('classId'=>$classId),
          array(
              'id',
              'const',
              'constValue',
              'deprecated',
              'description',
              'inherited',
              'name',
              'static',
              'type',
              'visibility',
              'hid'
          )
      );

      if(empty($list))
        $list = array();

      foreach ($list as $k=>$v){
          /**
           * @todo Optimize slow operation
           * recursive queries!
           */
          $desc = $this->findLocale('sysdocs_class_property' , 'description' , $language, $list[$k]['hid']);
          if(empty($desc)){
              $desc = nl2br($list[$k]['description']);
          }
          $list[$k]['description'] = $desc;
          $list[$k]['object_id'] = $list[$k]['id'];
      }

      return $list;
    }

    /**
     * Get class methods info by class id
     * @param integer $classId
     * @param string $lanuage
     * @return array
     */
    public function getClassMethods($classId , $language)
    {
      $propModel = Model::factory('sysdocs_class_method');
      $list = $propModel->getList(
          array('sort'=>'name','dir'=>'ASC') ,
          array('classId'=>$classId),
          array(
              'id',
              'name',
              'deprecated',
              'description',
              'inherited',
              'throws',
              'static',
              'abstract',
              'visibility',
              'returnType',
              'returnsReference',
              'hid',
              'final'
          )
      );

      if(empty($list))
          $list = array();

      $list = Utils::rekey('id', $list);

      foreach ($list as $k=>&$v)
      {
        /**
         * @todo Optimize slow operation
         * recursive queries!
         */
        $desc = $this->findLocale('sysdocs_class_method' , 'description' , $language, $list[$k]['hid']);
        if(empty($desc)){
            $desc = nl2br($list[$k]['description']);
        }
                
      	$list[$k]['description'] = $desc;
      	$list[$k]['object_id'] = $list[$k]['id'];
      	
      }unset($v);

      $params = Model::factory('sysdocs_class_method_param')->getList(
        array(
          'sort'=>'index',
          'order'=>'DESC'
        ),
        array(
          'methodId'=>array_keys($list)
        ),
        array(
            'methodId',
            'name',
            'default',
            'isRef',
            'description',
            'optional',
            'id'
        )
      );

      $params = Utils::groupByKey('methodId', $params);

      foreach ($list as $id=>&$data)
      {
        if(isset($params[$data['id']]))
        {
          $data['params'] = $params[$data['id']];
          $pList = Utils::fetchCol('name',  $params[$data['id']]);

          foreach ($pList as &$prop){
            $prop = '$' . $prop;
          }unset($prop);

          $data['paramsList'] = implode(', ', $pList);
        }else{
          $data['params'] = array();
          $data['paramsList'] = '';
        }
      }unset($data);

      return array_values($list);
    }
    
    /**
     * Update description
     * @param string $fileHid
     * @param integer $vers
     * @param string $language
     * @param string $text
     */
    public function setDescription($objectId , $fileHid, $vers , $language , $text , $docObject)
    {
      $data = Model::factory('sysdocs_localization')->getList(
          array('start'=>0,'limit'=>1,'sort'=>'vers','dir'=>'DESC'),
          array(
              'lang'=>$language,
              'object_class'=>$docObject,
              'hid'=>$fileHid,
              'object_id'=>$objectId,
              'field'=>'description',
              'vers'=>$vers
          ),
          array('id')
      );
      if(!empty($data)){
        $id = $data[0]['id'];
      }else{
        $id = false;
      }
      
      try{
        $o = new Db_Object('sysdocs_localization' , $id);
        $o->setValues(array(
          'field'=>'description',
          'hid'=>$fileHid,
          'lang'=>$language,
          'object_class'=>$docObject,
          'object_id'=>$objectId,
          'value'=>$text,
          'vers'=>$vers
        ));
        
        if(!$o->save())
          throw new Exception('Cannot update class description');
        
        return true;
      }catch (Exception $e){
        Model::factory('sysdocs_localization')->logError($e->getMessage());
        return false;
      }
    }
}