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
	 * Picklesオブジェクト
	 */
	private $px;

	/**
	 * 変換ルール
	 */
	private $rules;

	/**
	 * DOM抽出ルール
	 */
	private $dom_selectors;

	/**
	 * constructor
	 * @param object $px Picklesオブジェクト
	 * @param array $options オプション
	 */
	public function __construct($px, $options){
		$options = json_decode(json_encode($options));
		$this->px = $px;
		$this->rules = @$options->rules;
		$this->dom_selectors = @$options->dom_selectors;
	}

	/**
	 * パスを変換する
	 * @param  string $path 変換前のパス
	 * @param  string $cd   カレントディレクトリ
	 * @return string       変換後のパス
	 */
	public function convert( $path, $cd = null ){
		if( preg_match('/^(?:[a-zA-Z0-9]+\:|\/\/)/', $path) ){
			return $path;
		}

		// var_dump($path);
		if(is_null($cd)){ $cd = dirname($path); }
		$path = $this->px->fs()->get_realpath($path, $cd);//絶対パスに変換
		$path = $this->px->fs()->normalize_path($path);//normalize
		// var_dump($path);

		$rtn = $path;
		$rules = array();
		if( !@is_null($this->rules) ){
			$rules = $this->rules;
		}
		foreach( $rules as $rule ){
			if( !preg_match( $rule[0], $path ) ){
				continue;
			}
			$rtn = preg_replace( $rule[0], $rule[1], $path );
			break;
		}
		return $rtn;
	}

	/**
	 * HTMLファイル内のパスを変換する
	 * @param  string $src           変換前のHTMLソース
	 * @param  string $original_path 変換前のHTMLのパス
	 * @return string                変換後のHTMLソース
	 */
	public function convert_html( $src, $original_path ){
		$path_rewrited = $this->convert($original_path);
		// var_dump($original_path);

		// data-dec-blockブロックを削除
		$html = str_get_html(
			$src ,
			false, // $lowercase
			false, // $forceTagsClosed
			DEFAULT_TARGET_CHARSET, // $target_charset
			false, // $stripRN
			DEFAULT_BR_TEXT, // $defaultBRText
			DEFAULT_SPAN_TEXT // $defaultSpanText
		);

		$conf_dom_selectors = array(
			'*[href]'=>'href',
			'*[src]'=>'src',
			'form[action]'=>'action',
		);
		if( !@is_null($this->dom_selectors) ){
			$conf_dom_selectors = $this->dom_selectors;
		}

		foreach( $conf_dom_selectors as $selector=>$attr_name ){
			$ret = $html->find($selector);
			foreach( $ret as $retRow ){
				$val = $retRow->getAttribute($attr_name);
				$val = $this->convert($val, dirname($original_path));
				$retRow->setAttribute($attr_name, $val);
			}
		}


		$ret = $html->find('*[style]');
		foreach( $ret as $retRow ){
			$val = $retRow->getAttribute('style');
			$val = str_replace('&quot;', '"', $val);
			$val = str_replace('&lt;', '<', $val);
			$val = str_replace('&gt;', '>', $val);
			$val = $this->convert_css($val, $original_path);
			$val = str_replace('"', '&quot;', $val);
			$val = str_replace('<', '&lt;', $val);
			$val = str_replace('>', '&gt;', $val);
			$retRow->setAttribute('style', $val);
		}

		$ret = $html->find('style');
		foreach( $ret as $retRow ){
			$val = $retRow->innertext;
			$val = $this->convert_css($val, $original_path);
			$retRow->innertext = $val;
		}

		$src = $html->outertext;

		return $src;
	}

	/**
	 * CSSファイル内のパスを変換する
	 * @param  string $src           変換前のCSSソース
	 * @param  string $original_path 変換前のCSSのパス
	 * @return string                変換後のCSSソース
	 */
	public function convert_css( $src, $original_path ){
		$path_rewrited = $this->convert($original_path);
		// var_dump($original_path);

		$rtn = '';

		// url()
		while( 1 ){
			if( !preg_match( '/^(.*?)url\s*\\((.*?)\\)(.*)$/si', $src, $matched ) ){
				$rtn .= $src;
				break;
			}
			$rtn .= $matched[1];
			$rtn .= 'url("';
			$res = trim( $matched[2] );
			if( preg_match( '/^(\"|\')(.*)\1$/si', $res, $matched2 ) ){
				$res = trim( $matched2[2] );
			}
			$res = $this->convert( $res, dirname($original_path) );
			$rtn .= $res;
			$rtn .= '")';
			$src = $matched[3];
		}

		// @import
		$src = $rtn;
		$rtn = '';
		while( 1 ){
			if( !preg_match( '/^(.*?)@import\s*([^\s\;]*)(.*)$/si', $src, $matched ) ){
				$rtn .= $src;
				break;
			}
			$rtn .= $matched[1];
			$rtn .= '@import ';
			$res = trim( $matched[2] );
			if( !preg_match('/^url\s*\(/', $res) ){
				$rtn .= '"';
				if( preg_match( '/^(\"|\')(.*)\1$/si', $res, $matched2 ) ){
					$res = trim( $matched2[2] );
				}
				$res = $this->convert( $res, dirname($original_path) );
				$rtn .= $res;
				$rtn .= '"';
			}else{
				$rtn .= $res;
			}
			$src = $matched[3];
		}

		return $rtn;
	}

}
