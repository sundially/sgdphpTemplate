<?php
/*
*模板编译工具类
*模板解析主要由正则表达式实现
*version 1.0
*/
class CompileClass{
	private $template;//待编译文件
	private $content;//需要替换的文本
	private $comfile;//编译后的文本
	private $left='{';//左定界符
	private $right='}';//右定界符
	private $value=array();//值栈
	private $phpTurn;
	private $T_P=array();
	private $T_R=array();

	public function __construct($template,$compileFile,$config){
		$this->template=$template;
		$this->comfile=$compileFile;
		$this->content=file_get_contents($template);
		//根据开关变量，选择性进行转义
		if($config['php_turn']===false){//若不支持原生代码
			$this->T_P[]="#<\? (=|php |) (.+?)\?>#is";
			$this->T_R[]="&lt;? \\1\\2? &gt;";
		}
		//x7f=xff是ASCII码的16进制形式，即127-255
		$this->T_P[]='#\{\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\}#';
		//$this->T_P[]="#\{(loop|foreach)\\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)}#i";
		$this->T_P[]="#\{(loop|foreach) \\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\}#i";
		//$this->T_P[]="#\{\/(loop|foreach|if)}#i";
		$this->T_P[]="#\{\/(loop|foreach|if)\}#i";
		$this->T_P[]="#\{([K|V])\}#";
		$this->T_P[]="#\{if (.* ?)\}#i";
		$this->T_P[]="#\{(else if|elseif)(.* ?)\}#i";
		$this->T_P[]="#\{else\}#i";
		$this->T_P[]="#\{(\#|\* )(.* ?)(\#|\* )\}#";

		$this->T_R[]="<?php echo \$this->value['\\1'];?>";
		$this->T_R[]="<?php foreach ((array)\$this->value['\\2'] as \$K => \$V) { ?>";
		$this->T_R[]="<?php }?>";
		$this->T_R[]="<?php echo \$\\1; ?>";
		$this->T_R[]="<?php if (\\1){?>";
		$this->T_R[]="<?php }else if(\\2){ ?>";
		$this->T_R[]="<?php }else{ ?>";
		$this->T_R[]='';
	}
	public function compile(){
		$this->c_var2();
		$this->c_staticFile();
		file_put_contents($this->comfile, $this->content);
	}

	public function c_var2(){
		$this->content=preg_replace($this->T_P, $this->T_R, $this->content);
	}

	//加入对静态javascript文件的解析
	public function c_staticFile(){
		$this->content=preg_replace('#\{\!(.* ?)\!\}#', '<script src=\\1'.'?t='.time().'></script>', $this->content);
	}

	public function __set($name,$value){
		$this->$name=$value;
	}

	public function __get($name){
		return $this->$name;
	}
}