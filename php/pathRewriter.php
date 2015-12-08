<?php
/**
 * path rewriter
 */
namespace tomk79\pickles2\publishPathRewriter;

/**
 * path rewriter
 */
class pathRewriter{

	/**
	 * 変換ルール
	 */
	private $rules;

	/**
	 * constructor
	 * @param array $rules 変換ルール
	 */
	public function __construct($rules){
		$this->rules = $rules;
	}

	/**
	 * パスを変換する
	 * @param  string $path 変換前のパス
	 * @return string       変換後のパス
	 */
	public function convert( $path ){
		$rtn = $path;
		foreach( $this->rules as $rule ){
			if( !preg_match( $rule[0], $path ) ){
				continue;
			}
			$rtn = preg_replace( $rule[0], $rule[1], $path );
			break;
		}
		return $rtn;
	}

}
