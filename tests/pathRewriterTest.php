<?php
/**
 * test for tomk79/px2-publish-path-rewriter
 */
class pathRewriterTest extends PHPUnit_Framework_TestCase{

	/**
	 * 一般的テスト
	 */
	public function testStandardIO(){
		// make instance of pathRewriter
		$px = new picklesFramework2\px(__DIR__.'/testData/standard/px-files/');
		$pathRewriter = new tomk79\pickles2\publishPathRewriter\pathRewriter( $px, array(
			"rules"=>array(
				array('/\/abc\/([^\/]+)_files\/(.*)$/s', '/abc/img/$1_$2'),
			)
		) );

		$this->assertEquals( $pathRewriter->convert( '/abc/' ), '/abc/' );
		$this->assertEquals( $pathRewriter->convert( '/abc/abc_files/1.png' ), '/abc/img/abc_1.png' );
		$this->assertEquals( $pathRewriter->convert( '/abc/_files/1.png' ), '/abc/_files/1.png' );

		$px->site()->__destruct();
		$px = null;
		unset($px);
	}//testStandardIO()

}
