<?php
class Model_Group extends Model
{
	/**
	 * Get group names indexed by group id
	 * @return array
	 */
	public function getGroups()
	{
		$cache = self::$_dataCache;  

		/*
		 * Check cache 
		 */
		if($cache && $data = $cache->load('groups_list'))
			return $data;
		
		$data = array();
		$sql = $this->_db->select()->from($this->table() , array('id' , 'title'));
		$data = $this->_db->fetchAll($sql);
		if(!empty($data))
			$data = Utils::collectData('id', 'title', $data);		
		/*
		 * Store cache
		 */	
		if($cache)
			$cache->save($data, 'groups_list');

		return $data;	
	}
	/**
	 * Add users group
	 * @param string  $title - group name
	 * @return intger - Id of created group
	 */
	public function addGroup($title)
	{
		$obj = new Db_Object($this->_name);
		$obj->set('title', $title);
		
		if(!$obj->save())
			return false;
			
		$cache = self::$_dataCache;  
		/**
		 * Invalidate cache
		 */	
		if($cache)
			$cache->remove('groups_list');
		
		return $obj->getId();
	}
	/**
	 * Remove users Group
	 * @param integer $id
	 * @return boolean
	 */
	public function removeGroup($id)
	{
		$obj = new Db_Object($this->_name, $id);
        
		if(!$obj->delete())
			return false;
		
		$cache = self::$_dataCache;  
		/**
		 * Invalidate cache
		 */	
		if($cache)
			$cache->remove('groups_list');
		
		return true;	
	}
}