<?php
/*
 * DVelum project http://code.google.com/p/dvelum/ , http://dvelum.net
 * Copyright (C) 2011-2013  Kirill A Egorov
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
/**
 * Ext data store implementation
 * @package Ext
 */
class Ext_Store extends Ext_Object
{
    protected  $_fields = array();
    

	/**
	 * Add store field
	 * @param Ext_Virtual | array $object
	 * @return boolean
	 */
	public function addField($object)
	{
	    if($object instanceof Ext_Virtual && $object->getClass()=='Data_Field')
	    {
	        if(empty($object->name))
	            return false;
	    }
	    elseif(is_array($object))
	    {
	        if(!isset($object['name']))
	            return false;
	        
	        $object = Ext_Factory::object('Data_Field' , $object);
	    }
	    else
	    {
	        return false;
	    }

	    $this->_fields[$object->name] = $object;
	    
	    return true;
	}
	
	/**
	 * Add fields from array (configs or Ext_Virtual)
	 * @param array $fields
	 */
	public function addFields(array $fields)
	{
	    foreach($fields as $field)
	        $this->addField($field);
	}
	
	/**
	 * Get store field
	 * @param string $name
	 * @throws Exception
	 * @return Ext_Virtual
	 */
	public function getField($name)
	{
	    $this->_convertFields();
	    
	    if(!isset($this->_fields[$name]))
	        throw new Exception('Cannot find field:' . $name);
	    
	    return $this->_fields[$name];
	}
	
	/**
	 * Get store fields
	 * @return array
	 */
	public function getFields()
	{
	    $this->_convertFields();
	    
	    return $this->_fields;
	}
	
	/**
	 * Remove store fields
	 */
	public function resetFields()
	{
	    $this->_convertFields();
	    $this->_fields = array();
	}
	
	/**
	 * Remove store field by name
	 * @param string $name
	 */
	public function removeField($name)
	{
	    $this->_convertFields();
	    
	    if(isset($this->_fields[$name]))
	        unset($this->_fields[$name]);
	}
	
	/**
	 * Rename store field
	 * @param string $oldName
	 * @param string $newName
	 * @return boolean
	 */
	public function renameField($oldName , $newName)
	{
	    $this->_convertFields();
	    
	    if(empty($newName) || !isset($this->_fields[$oldName]) || isset($this->_fields[$newName]))
	        return false;
	    
	    $cfg = $this->getField($oldName);
	    $cfg->name = $newName;
	    $this->removeField($oldName);
	    $this->addField($cfg->getConfig()->__toArray(true));	    
	    return true;
	}
	
	/**
	 * Check if field exists
	 * @param string $name
	 * @return boolean
	 */
	public function fieldExists($name)
	{
	    $this->_convertFields();
	    return isset($this->_fields[$name]);
	}
	
	
	public function __get($name)
	{
	    $this->_convertFields();
	    
	    if($name === 'fields')	     
	        return array_values($this->getFields()); 	        	    
	    else	    
	        return parent::__get($name);	    
	}
	
	/**
	 * Convert fields from config property to the local variable
	 */
	protected function _convertFields()
	{
	    if(empty($this->_config->fields))
	        return;
	    
        $fields = json_decode($this->_config->fields , true);

        if(!empty($fields))
        {
            foreach ($fields as $field){
                if(!isset($this->_fields[$field['name']]))
                    $this->addField($field);
            }
        }
        $this->fields = '';
	}
	
	public function __toString()
	{
	    $this->_convertFields();
		$this->_convertListeners();		
		$fields = array();

		if(!empty($this->_fields))
		    $this->fields = "[\n".Utils_String::addIndent(implode(',',array_values($this->_fields)))."\n]";
				
		if($this->_config->isValidProperty('model') && strlen($this->_config->model))
		{			
			$model = Ext_Code::appendNamespace($this->_config->model);	
			$this->_config->model = $model;
		}	
		return $this->_config->__toString();
	}
}