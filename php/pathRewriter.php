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
	 * constructor
	 * @param object $px Picklesオブジェクト
	 * @param array $rules 変換ルール
	 */
	public function __construct($px, $rules){
		$this->px = $px;
		$this->rules = $rules;
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
		// var_dump($path);

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

		$ret = $html->find('*[href]');
		foreach( $ret as $retRow ){
			$val = $retRow->getAttribute('href');
			$val = $this->convert($val, dirname($original_path));
			$retRow->setAttribute('href', $val);
		}

		$ret = $html->find('*[src]');
		foreach( $ret as $retRow ){
			$val = $retRow->getAttribute('src');
			$val = $this->convert($val, dirname($original_path));
			$retRow->setAttribute('src', $val);
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

		$ret = $html->find('form[action]');
		foreach( $ret as $retRow ){
			$val = $retRow->getAttribute('action');
			$val = $this->convert($val, dirname($original_path));
			$retRow->setAttribute('action', $val);
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
			$rtn .= '@import "';
			$res = trim( $matched[2] );
			if( preg_match( '/^(\"|\')(.*)\1$/si', $res, $matched2 ) ){
				$res = trim( $matched2[2] );
			}
			$res = $this->convert( $res, dirname($original_path) );
			$rtn .= $res;
			$rtn .= '"';
			$src = $matched[3];
		}

		return $rtn;
	}

	// /**
	//  * 変換後の新しいパスを取得
	//  */
	// private function get_new_path( $path ){
	// 	if( preg_match( '/^(?:[a-zA-Z0-9]+\:|\/\/|\#)/', $path ) ){
	// 		return $path;
	// 	}
	// 	$cd = $this->px->href( $this->px->req()->get_request_file_path() );
	// 	$cd = preg_replace( '/^(.*)(\/.*?)$/si', '$1', $cd );
	// 	if( !strlen($cd) ){
	// 		$cd = '/';
	// 	}
	//
	// 	switch(strtolower($this->options->to)){
	// 		case 'relate':
	// 			// 相対パスへ変換
	// 			$path = $this->px->fs()->get_realpath($path, $cd);
	// 			$path = $this->px->fs()->get_relatedpath($path, $cd);
	// 			break;
	// 		case 'absolute':
	// 			// 絶対パスへ変換
	// 			$path = $this->px->fs()->get_realpath($path, $cd);
	// 			break;
	// 		case 'pass':
	// 		default:
	// 			// 処理を行わない
	// 			break;
	// 	}
	//
	// 	$path = $this->px->fs()->normalize_path($path);
	//
	// 	if( @is_null($this->options->supply_index_filename) ){
	// 		// null なら処理しない
	// 	}elseif( $this->options->supply_index_filename ){
	// 		// 省略されたインデックスファイル名を付与
	// 		$path = preg_replace('/\/((?:\?|\#).*)?$/si','/'.$this->px->get_directory_index_primary().'$1',$path);
	// 	}else{
	// 		// 省略できるインデックスファイル名を削除
	// 		$path = preg_replace('/\/(?:'.$this->px->get_directory_index_preg_pattern().')((?:\?|\#).*)?$/si','/$1',$path);
	// 	}
	//
	// 	return $path;
	// }

}
