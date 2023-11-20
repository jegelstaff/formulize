<?php

use PHPUnit\Framework\TestCase;

require 'functions.php';

// Empty mock classes
class FormHandler {}
class xoopsDB {}
class xoopsUser {}

final class TestFormulizeFunctions extends TestCase
{
	// getCurrentURL()
	public function test_getCurrentURL_basic_functionality() {
		// Arrange
		$_SERVER['HTTP_HOST'] = 'test.net';
		$_SERVER['REQUEST_URI'] = "/index.php";
		// Act/Assert
		$this->assertEquals('http://test.net/index.php', getCurrentURL());
	}
	public function test_getCurrentURL_escape_html_tags() {
		// Arrange
		$_SERVER['HTTP_HOST'] = 'test.net';
		$_SERVER['REQUEST_URI'] = "/index.php?var1=<script>console.log('hello world');</script><html>&amp;</html>";
		// Act/Assert
		$this->assertEquals('http://test.net/index.php?var1=console.log(&#039;hello world&#039;);&amp;', getCurrentURL());
	}

	// getFormTitle()
	public function test_getFormTitle_html_safe_title() {
		// Arrange
		$fid = 123;
		$inputTitle = '&lt;h1&gt;My awesome title&lt;/h1&gt;';
		$expectTitle = '<h1>My awesome title</h1>';
		$mockFormHandlerClass = $this->getMockBuilder('FormHandler')->addMethods(['get', 'getVar'])->getMock();
		$mockFormHandlerClass
			->expects($this->once())
			->method('getVar')
			->willReturn($inputTitle);
		$mockFormHandlerClass
			->expects($this->once())
			->method('get')
			->with($fid)
			->willReturnSelf();

		// Act
		$resultTitle = getFormTitle($fid, $mockFormHandlerClass);

		// Assert
		$this->assertEquals($expectTitle, $resultTitle);
	}

	// getSavedViewOwner()
	public function test_getSavedViewOwner_result() {
		// Arrange
		$vid = 1;
		$uid = 666;
		$prefix = '123456789_formulize_saved_views';
		$queryResult = array('queryResult');
		$queryFetchedArray  = array('sv_owner_uid' => $uid);
		$query = 'SELECT sv_owner_uid FROM ' . $prefix . ' WHERE sv_id = ' . $vid;

		$mockXoopsDB = $this->getMockBuilder('XoopsDB')->addMethods(['prefix', 'query', 'fetchArray'])->getMock();
		$mockXoopsDB
			->expects($this->once())
			->method('prefix')
			->with('formulize_saved_views')
			->willReturn($prefix);
		$mockXoopsDB
			->expects($this->once())
			->method('query')
			->with($query)
			->willReturn($queryResult);
		$mockXoopsDB
			->expects($this->once())
			->method('fetchArray')
			->with($queryResult)
			->willReturn($queryFetchedArray);

		global $xoopsDB;
		$xoopsDB = $mockXoopsDB;

		// Act/Assert
		$this->assertEquals($uid, getSavedViewOwner($vid));
	}
	public function test_getSavedViewOwner_no_result() {
		// Arrange
		$vid = 1;
		$uid = 0;
		$prefix = '123456789_formulize_saved_views';
		$queryResult = array('queryResult');
		$queryFetchedArray  = array('sv_owner_uid' => $uid);
		$query = 'SELECT sv_owner_uid FROM ' . $prefix . ' WHERE sv_id = ' . $vid;

		$mockXoopsDB = $this->getMockBuilder('XoopsDB')->addMethods(['prefix', 'query', 'fetchArray'])->getMock();
		$mockXoopsDB
			->expects($this->once())
			->method('prefix')
			->with('formulize_saved_views')
			->willReturn($prefix);
		$mockXoopsDB
			->expects($this->once())
			->method('query')
			->with($query)
			->willReturn($queryResult);
		$mockXoopsDB
			->expects($this->once())
			->method('fetchArray')
			->with($queryResult)
			->willReturn($queryFetchedArray);

		global $xoopsDB;
		$xoopsDB = $mockXoopsDB;

		// Act/Assert
		$this->assertEquals(false, getSavedViewOwner($vid));
	}


	// q()
	public function test_q() {
		// Arrange
		$query = 'SELECT * FROM "groups"';
		$queryArray = array();
		$mockXoopsDB = $this->getMockBuilder('XoopsDB')->addMethods(['query', 'fetchArray'])->getMock();
		$mockXoopsDB
			->expects($this->once())
			->method('query')
			->with($query)
			->willReturnSelf();
		$mockXoopsDB
			->expects($this->once())
			->method('fetchArray')
			->willReturn($queryArray);

		global $xoopsDB;
		$xoopsDB = $mockXoopsDB;

		// Act / Assert
		$result = q($query);

		// Assert
		$this->assertEquals(array(), $result);
	}

	// groupNameList()
	// public function test_groupNameList() {
	// 	// Arrange
	// 	$prefix = '123456789_groups';
	// 	$groupIds = '1,2,3';
	// 	$mockXoopsDB = $this->getMockBuilder('XoopsDB')->addMethods(['prefix'])->getMock();
	// 	$mockXoopsDB
	// 		->expects($this->once())
	// 		->method('prefix')
	// 		->with('groups')
	// 		->willReturn($prefix);
	// 	$mockXoopsUser = $this->getMockBuilder('XoopsUser')->addMethods(['getGroups'])->getMock();
	// 	$mockXoopsUser
	// 		->expects($this->once())
	// 		->method('getGroups')
	// 		->willReturn();

	// 	global $xoopsDB;
	// 	$xoopsDB = $mockXoopsDB;
	// 	global $xoopsUser;
	// 	$xoopsUser = $mockXoopsUser;

	// 	// Act/Assert
	// 	$this->assertEquals('', groupNameList());
	// }
}
