<?

// *****************************************
// THESE CONSTANTS MUST BE SPECIFIED IN ORDER TO CONNECT TO THE DATABASE
// *****************************************

	define('DBHOST', 'localhost');
	define('DBUSER', '');
	define('DBPASS', '');
	define('DBNAME', '');

	define('PATH', '../httpdocs/XOOPS/');  // physical path to the root of the XOOPS installation (can be relative to the current directory)

	define('FRAMEWORK', ''); // name of framework
	define('DEFAULTFORM', ''); // handle of form in framework
	define('DEFAULTFILTER', ''); // need to determine syntax!

	define('LANG', 'English');

// *****************************************
// CONNECTION FUNCTIONS FOLLOW
// *****************************************

include PATH . "modules/formulize/include/extract.php";

// ****************************************
// DISPLAY LOGIC FOLLOWS
// ****************************************

	// receive any specified params, or use defaults
	if(isset($_GET['form'])) {
		$form = $_GET['form'];
	}
	if(isset($_GET['filter'])) {
		$filter = $_GET['filter'];
	}
	if(isset($_POST['form'])) {
		$form = $_POST['form'];
	}
	if(isset($_POST['filter'])) {
		$filter = $_POST['filter'];
	}
	
	$data = getData();

	print_r($data);

?>