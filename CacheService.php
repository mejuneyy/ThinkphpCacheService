<?php
	
	/**
	 * cache 公共类
	 */
	class CacheService
	{
		//单例对象
		private static $_instance;

		//总类集合
		protected $_classP;

		protected $_cache;

		//禁止克隆
		private function __clone(){
			
		}

		/**
		 * 构造函数
		 */
		private function __construct()
		{
			$this->_cache = Cache(array('type' => C('DATA_CACHE_TYPE') ,'expire' => C('DATA_CACHE_TIME')));
		}

		//单例
		public static function GI()
		{	
			if(! (self::$_instance instanceof self))
			{
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * 赋值需要缓存的model (表) 类
		 * @param  string $name model (表)
		 * @return void
		 */
		private function getService( $name )
		{
			if(!isset($this->_classP[$name]))
			{
				$this->_classP[$name] = $this->initServiceClass($name);
			}
		}

		/**
		 * 初始化缓存model (表) 类
		 * @param  string $name model (表)
		 * @return array
		 */
		protected function initServiceClass( $name )
		{
			$serviceArray = array();

			//查询语句
			$serviceArray['cacheName'] = $name;

			//模型
			$_tmp_model = D($name);
			$serviceArray['model'] = empty($_tmp_model) ? M($name) : $_tmp_model;

			//查询语句
			$serviceArray['map'] = array();

			return $serviceArray;
		}


		/**
		 * 单调缓存数据
		 * @param  [string] $name 	[model名]
		 * @param  [int] $id   		[主键值]
		 * @param  [string] $field  [字段名]
		 * @return [array]getCacheById
		 */
		public function getCacheById( $name, $id, $field = null)
		{
			//获取服务
			$this->getService($name);
			//取得主键
			$pk =  $this->_classP[$name]['model']->getPk();
            
			//获取缓存
			//$modelArray = $this->_cache->get($name."_".$pk."_".$id);

			//判断是否有缓存
			//if(empty($modelArray))
			//{
				$modelArray = $this->_classP[$name]['model']->where(array($pk=>$id))->find();
			//	$this->_cache->set($name."_".$pk."_".$id, $modelArray);
			//}
			return empty($field) ? $modelArray : $modelArray[$field];
		}
        
        /**
		 * 批量取值
		 * @param  [string] $name 	[model名]
		 * @param  [int] $id   		[主键值]
		 * @param  [string] $field  [字段名]
		 * @return [array]
		 */
		public function getCacheByList( $name, $list, $field = null)
		{
			$list = is_array($list) ? $list : array_map("trim", explode(",", $list));
            foreach ($list as $id) {
                $ret[] = $this->getCacheById($name, $id, $field);
            }
            return $ret;
		}


		/**
		 * 根据条件批量取值
		 * @param  [type] $name  [description]
		 * @param  [type] $map   [description]
		 * @param  [type] $filed [description]
		 * @return [type]        [description]
		 */
		public function getCacheByMap ($name, $map, $field = null)
		{

			//获取服务
			$this->getService($name);

			//缓存key
			$key = $name.implode('_', $map);

			//取ID队列
			$idList = $this->_cache->get($key);

			//取得主键
			$pk =  $this->_classP[$name]['model']->getPk();

			if(empty($idList))
			{
				$idList = array();
				$dataList = $this->_classP[$name]['model']->where($map)->select();
				foreach ($dataList as $key => $data) {
					$idList[] = $data[$pk];
				}
			}
			return $this->getCacheByList($name, $idList, $field);
		}

		/**
		 * 根据主键删除缓存
		 * @param  [string] $name 	[model名]
		 * @param  [int] $id   		[主键值]
		 * @param  [boolean] $deleteDB   		[是否删除数据库数据]
		 */
		public function delCacheById( $name, $id, $deleteDB = false )
		{
			//获取服务
			$this->getService($name);
			//取得主键
			$pk =  $this->_classP[$name]['model']->getPk();
			//获取缓存
			$modelArray = $this->_cache->get($name."_".$pk."_".$id);
			//判断是否有缓存
			if( ! empty($modelArray))
			{
				$this->_cache->set($name."_".$pk."_".$id, null);
			}
			//判断是否删除数据库数据
			if( $deleteDB == true)
			{
				$this->_classP[$name]['model']->where(array($pk=>$id))->delete();
			}
			return ;
		}

		/**
		 * 保存修改
		 * 
		 * @param  string $name  [model名]
		 * @param  array $map     [查询条件]
		 * @param  string $data    [设置数组]
		 * @param  array  $options [附加参数]
		 * @param  boolean  $refreshModel [是否要刷新model]
		 * @return array          [结果数组]
		 */
		public function saveWithCache($name, $map, $data='', $options=array(), $refreshModel = false )
		{
			//判断是否要刷新model
			if($refreshModel == ture)
			{
				 $this->_classP[$name] = array();
			}
			//获取服务
			$this->getService($name);
			//取得主键
			$pk = $this->_classP[$name]['model']->getPk();
			//数据库保存
			$rs = $this->_classP[$name]['model']->where($map)->data($data)->save();
			//结果查询
			$dbData = $this->_classP[$name]['model']->where($map)->find();
			//删除原有缓存
			$this->delCacheById($name, $dbData[$pk]);
			//返回数据
			return $dbData;
		}
	}
?>