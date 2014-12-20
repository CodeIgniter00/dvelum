<?php
class Backend_Designer_Sub_Viewframe extends Backend_Designer_Sub
{
	public function indexAction()
	{
		if(!$this->_session->keyExists('loaded') || !$this->_session->get('loaded')){
			Response::put('');
			exit;
		}

		$designerConfig = Config::factory(Config::File_Array,  $this->_configMain['configs'] . 'designer.php');

		$res = Resource::getInstance();
		$res->addJs('/js/lib/jquery.js'  , 0);
		Model::factory('Medialib')->includeScripts();

		$res->addJs('/js/app/system/SearchPanel.js');
		$res->addJs('/js/app/system/HistoryPanel.js', 0);
		$res->addJs('/js/lib/ext_ux/RowExpander.js', 0);
		$res->addJs('/js/app/system/RevisionPanel.js', 1);
        $res->addJs('/js/app/system/EditWindow.js' , 2);
        $res->addJs('/js/app/system/ContentWindow.js' , 3);
		$res->addJs('/js/app/system/designer/viewframe/main.js',4);
		$res->addJs('/js/app/system/designer/lang/'.$designerConfig['lang'].'.js',5);

		$project = $this->_getProject();
		$projectCfg = $project->getConfig();

		Ext_Code::setRunNamespace($projectCfg['runnamespace']);
		Ext_Code::setNamespace($projectCfg['namespace']);

		$grids = $project->getGrids();

		if(!empty($grids))
		{
			foreach ($grids as $name=>$object)
			{
				if($object->isInstance())
				  continue;

				$cols = $object->getColumns();
				if(!empty($cols))
					foreach($cols as $column)
						$column['data']->itemId = $column['id'];

				$object->addListener('columnresize','{
							 fn:function( ct, column, width,eOpts){
								frApp.app.onGridColumnResize("'.$name.'", ct, column, width, eOpts);
							 }
				}');

				$object->addListener('columnmove','{
							fn:function(ct, column, fromIdx, toIdx, eOpts){
								frApp.app.onGridColumnMove("'.$name.'", ct, column, fromIdx, toIdx, eOpts);
							}
				}');
			}
		}

		$key = 'vf_'.Designer_Factory::getProjectCacheKey($this->_session->get('file'));

		$templates = $designerConfig->get('templates');
    	$replaces = array(
    			array('tpl'=>$templates['wwwroot'],'value'=>$this->_configMain->get('wwwroot')),
    			array('tpl'=>$templates['adminpath'],'value'=>$this->_configMain->get('adminPath')),
    			array('tpl'=>$templates['urldelimeter'],'value'=>$this->_configMain->get('urlDelimetr')),
    	);

    	$includes = Designer_Factory::getProjectIncludes($key, $project , false , $replaces);

		if(!empty($includes))
		{
			foreach ($includes as $file)
			{
	            if(File::getExt($file) == '.css')
			       $res->addCss($file , false);
			    else
				   $res->addJs($file , false, false);
			}
		}

		$names = $project->getRootPanels();

		$basePaths = array();

		$parts = explode('/', $this->_configMain->get('wwwroot'));
		if(is_array($parts) && !empty($parts)){
			foreach ($parts as $item){
				if(!empty($item)){
				    $basePaths[] = $item;
				}
			}
		}

		$basePaths[] = $this->_configMain['adminPath'];
		$basePaths[] = 'designer';
		$basePaths[] = 'sub';

		$initCode = '
		app.delimeter = "'.$this->_configMain['urlDelimetr'].'";
		app.admin = "' . $this->_configMain->get('wwwroot') . $this->_configMain->get('adminPath').'";
		app.wwwRoot = "' . $this->_configMain->get('wwwroot') . '";

		var applicationClassesNamespace = "'.$projectCfg['namespace'].'";
		var applicationRunNamespace = "'.$projectCfg['runnamespace'].'";
		var designerUrlPaths = ["'.implode('","', $basePaths).'"];
		var canDelete = true;
		var canPublish = true;
		var canEdit = true;

		' . $project->getCode($replaces) . '

		Ext.onReady(function(){
    		Ext.create("frApp.Application",{
    			listeners:{
    				"launch":{
    					fn:function(){
						';

		if(!empty($names))
		{
			foreach ($names as $name)
			{
				if($project->getObject($name)->isExtendedComponent()){

				    if($project->getObject($name)->getConfig()->defineOnly)
				      continue;

					$initCode.= Ext_Code::appendRunNamespace($name).' = Ext.create("'.Ext_Code::appendNamespace($name).'",{});';
				}
			    $initCode.='frApp.viewFrame.add('.Ext_Code::appendRunNamespace($name).');';
			}
		}
		$initCode.='
    					}
    				}
    			}
    		});
		});';

		$res->addInlineJs($initCode);

		$tpl = new Template();
		$tpl->lang = $this->_configMain['language'];
		$tpl->development = $this->_configMain['development'];
		$tpl->resource = $res;
		$tpl->useCSRFToken = Registry::get('backend' , 'config')->get('use_csrf_token');

		Response::put($tpl->render(Application::getTemplatesPath().'designer/viewframe.php'));
	}
}