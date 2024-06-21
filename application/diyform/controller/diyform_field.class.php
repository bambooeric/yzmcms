<?php
/**
 * 后台管理中心 - 自定义表单字段管理
 * @author           袁志蒙  
 * @license          http://www.yzmcms.com
 * @lastmodify       2018-01-13
 */
 
 
defined('IN_YZMPHP') or exit('Access Denied'); 
yzm_base::load_controller('common', 'admin', 0);
yzm_base::load_common('lib/sql'.EXT, ROUTE_M);

class diyform_field extends common{
	
	
	private $modelid, $modeltable, $modelname;
	public function __construct() {
		parent::__construct();
		$this->modelid = isset($_GET['modelid']) ? intval($_GET['modelid']) : 1;
		$this->public_set_modelinfo();
	}


	/**
	 * 字段列表
	 */
	public function init() {
		$modelid = $this->modelid;
		$data = D('model_field')->where(array('modelid' => $modelid))->order('listorder ASC,fieldid ASC')->select();
		include $this->admin_tpl('diyform_field_list');
	}

	
	/**
	 * 添加字段
	 */
	public function add() {

		if(isset($_POST['dosubmit'])) {
			
		   if(!preg_match('/^[a-zA-Z]{1}([a-zA-Z0-9]|[_]){0,29}$/', $_POST['field'])) showmsg('字段名格式不正确！');
		   
		   $files = array('input','textarea','number','decimal','datetime','image','images','attachment','attachments','select','radio','checkbox');
		   if(!in_array($_POST['fieldtype'], $files))  showmsg(L('illegal_parameters'), 'stop');
		   $_POST = new_html_special_chars($_POST);
		   
		   $_POST['issystem'] = 0;	
		   $_POST['modelid'] = $this->modelid;	
		   $_POST['listorder'] = 1;	
		   
		   if(in_array($_POST['fieldtype'], array('select','radio','checkbox'))){
			   $_POST['setting'] = array2string(explode('|', rtrim($_POST['setting'], '|')));
		   }elseif($_POST['fieldtype']=='datetime'){
			   $_POST['setting'] = $_POST['dateset'] ? '{"0":"1"}' : '';
		   }else{
			   unset($_POST['setting']);
		   }
		   
		   if($_POST['minlength']) $_POST['isrequired'] = 1;

		   if($_POST['fieldtype'] == 'input' || $_POST['fieldtype'] == 'datetime'){
				sql::sql_add_field($this->modeltable, $_POST['field']);
		   }else if($_POST['fieldtype'] == 'textarea' || $_POST['fieldtype'] == 'images' || $_POST['fieldtype'] == 'attachments'){
				sql::sql_add_field_mediumtext($this->modeltable, $_POST['field']);
		   }else if($_POST['fieldtype'] == 'editor' || $_POST['fieldtype'] == 'editor_mini'){
				sql::sql_add_field_text($this->modeltable, $_POST['field']);
		   }else if($_POST['fieldtype'] == 'number'){
				sql::sql_add_field_int($this->modeltable, $_POST['field'], intval($_POST['defaultvalue']));
		   }else if($_POST['fieldtype'] == 'decimal'){
				sql::sql_add_field_decimal($this->modeltable, $_POST['field']);
		   }else{
				sql::sql_add_field($this->modeltable, $_POST['field'], $_POST['defaultvalue'], $_POST['maxlength']);  
		   }

		   D('model_field')->insert($_POST); 
		   delcache($this->modelid.'_model');
		   showmsg(L('operation_success'), U('init',array('modelid'=>$this->modelid)), 1);
		}else{
			$modelid = $this->modelid;
			$modelname = $this->modelname;
			include $this->admin_tpl('diyform_field_add');
		}
	}
	
	
	/**
	 * 修改字段
	 */
	public function edit() {
		$fieldid = isset($_GET['fieldid']) ? intval($_GET['fieldid']) : 0;
		if(isset($_POST['dosubmit'])) {

			$_POST = new_html_special_chars($_POST);
			
			if(in_array($_POST['fieldtype'], array('select','radio','checkbox'))){
			   $_POST['setting'] = array2string(explode('|', rtrim($_POST['setting'], '|')));
			}elseif($_POST['fieldtype']=='datetime'){
			   $_POST['setting'] = $_POST['dateset'] ? '{"0":"1"}' : '';
			}else{
			   unset($_POST['setting']);
			}
		   	
			unset($_POST['issystem'], $_POST['modelid'], $_POST['fieldtype']);	
			
		    $_POST['isrequired'] = $_POST['minlength'] ? 1 :0;
			if(D('model_field')->update($_POST, array('fieldid' => $fieldid))){
				delcache($this->modelid.'_model');
				showmsg(L('operation_success'), U('init',array('modelid'=>$this->modelid)), 1);
			}else{
				showmsg(L('data_not_modified'), U('init',array('modelid'=>$this->modelid)));
			}
		}else{
			$modelid = $this->modelid;
			$modelname = $this->modelname;
			$data = D('model_field')->where(array('fieldid'=>$fieldid))->find();
			include $this->admin_tpl('diyform_field_edit');
		}
	}
	
	
	/**
	 * 删除字段
	 */
	public function delete() {
		$fieldid = isset($_GET['fieldid']) ? intval($_GET['fieldid']) : 0;
		$model_field = D('model_field'); 
		$data = $model_field->field('field,issystem')->where(array('fieldid' => $fieldid))->find();
		if(!$data['issystem']){
			$model_field->delete(array('fieldid' => $fieldid));
			sql::sql_del_field($this->modeltable, $data['field']);  
			delcache($this->modelid.'_model');
			showmsg(L('operation_success'), U('init',array('modelid'=>$this->modelid)), 1);
		}else{
			showmsg('不能删除系统字段！');
		}
	}
	
	
	/**
	 * 排序字段
	 */
	public function order() {
		if(isset($_POST['fieldid']) && is_array($_POST['fieldid'])){
			$model_field = D('model_field'); 
			foreach($_POST['fieldid'] as $key=>$val){
				$model_field->update(array('listorder'=>$_POST['listorder'][$key]),array('fieldid'=>intval($val)));
			}
			delcache(intval($_POST['modelid']).'_model');
		}
		showmsg(L('operation_success'), '' ,1);
	}


	/**
	 * ajax修改状态
	 */
	public function public_change_status() {
		if(is_post()){
			$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
			$value = isset($_POST['value']) ? intval($_POST['value']) : 0;
			$data = D('model_field')->field('modelid,issystem')->where(array('fieldid' => $id))->find();
			if($data['issystem']) return_json(array('status'=>0,'message'=>'系统字段不允许修改！'));

			$disabled = $value ? 0 : 1;
			if(D('model_field')->update(array('disabled' => $disabled), array('fieldid' => $id))){
				delcache($data['modelid'].'_model');
				return_json(array('status'=>1,'message'=>L('operation_success')));
			}else{
				return_json();
			}
		}
	}

	
	/**
	 * 检查字段
	 */
	public function public_check_field() {
		$field = isset($_GET['field']) ? $_GET['field'] : '';
		if(!$field) exit('-1');  
		$model = D($this->modeltable);
		$fields = $model->get_fields();
		if(!in_array($field, $fields))
		   exit('1');  
		else
		   exit('0');  
	}
	
	
	
	/**
	 * 获取模型信息
	 */
	private function public_set_modelinfo() {
		$data = D('model')->field('name,tablename')->where(array('modelid'=>$this->modelid))->find();
		if($data){
			$this->modelname = $data['name'];
			$this->modeltable = $data['tablename'];
		}else{
			return_message('模型不存在！', 0);
		}
	}

}