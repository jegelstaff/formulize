<?php
/**
 * Tests for the code files that element types write into modules/formulize/code/.
 *
 * Six element types can hold admin-authored PHP in ele_value, and all of them keep that code in a file
 * rather than only in the database: captionedContent and fullWidthContent (content containing $value),
 * text, number and textarea (a default value containing $default), and derived (its formula). Three
 * different setVar/getVar pairs implement this - formulizeStaticContentElement, formulizeTextElement
 * (inherited by number and textarea) and formulizeDerivedElement - which is why this file checks all six
 * rather than one of each shape.
 *
 * WHAT IS BEING PROTECTED
 *
 * 1. THE OBJECT CARRIES THE VALUE IT WAS GIVEN. These setVar implementations used to blank the code out
 *    of ele_value once it had been written to the file, on the reasoning that the file was the only copy
 *    that mattered. Saving an element sets ele_value TWICE - once in the type handler's adminSave(), and
 *    again inside formulizeHandler::upsertElementSchemaAndResources(), which is handed whatever the first
 *    pass left in the object (see element_options_save.php, which reads $element->vars directly). A blank
 *    is indistinguishable from "the admin removed the code", so the second pass deleted the file that the
 *    first pass had just written, and the content was gone from the file and the database both. So each
 *    type is checked twice over: the file is right, AND the object is still holding the real value.
 *
 * 2. REMOVING THE CODE REMOVES THE FILE. The types that can hold either code or a plain value must delete
 *    the file when the value stops being code, or the file goes on winning on read (see 3) and the change
 *    silently fails to take. The mirror of 1: a delete must happen when it is meant to, and never when it
 *    is not - a caller setting other keys of ele_value must not delete the file by omission.
 *
 * 3. THE FILE IS THE TRUTH ON READ. getVar prefers the file over the stored value, which is what lets code
 *    be edited on disk and picked up without a save. The check reads a value the way the database would
 *    supply it (assignVars with a serialized array), then changes the file underneath and reads again.
 *
 * 4. THE DATABASE COPY IS ONLY DROPPED ONCE THE FILE HOLDS IT. insert() strips the code out of the copy it
 *    writes, so there is one source of truth rather than two - but only when the file is actually there and
 *    has something in it. Code can legitimately be in ele_value with no file (patch 001 wrote the files and
 *    left the database copies alone, cloneForm() copies element rows with raw SQL and no files), and such an
 *    element works, because getVar falls back to the stored value. insert() is handed that stored value
 *    whenever ele_value was not the property that changed, so a strip on the shape of the content alone
 *    would empty those elements during a save that never touched their content.
 *
 * The regression that prompted all of this also had a second form: formulizeElement::setVar carried its
 * own copy of the textarea rule, so a textarea wrote its file in formulizeTextElement::setVar and then
 * deleted it again in the parent call on the next line. The textarea rows below cover that.
 *
 * WHAT THIS DOES NOT COVER
 * The admin save path itself, and whether the content survives all the way to the screen - those need a
 * browser and a built system, and are covered by
 * tests/e2e/formulize-core/validate/041-static-content-code-files.spec.js, which turns the museum system's
 * Survey Intro element into PHP code, saves it twice, and checks the value that code computes appears on
 * the survey form.
 *
 * Unlike the standalone logic tests in this folder, this one boots the application, because element
 * objects cannot be built without it. It runs after the Playwright suite in CI for that reason. It does
 * not read or write any data of its own: every element is built in memory, and the only files it touches
 * are named for a handle no real element uses, which it deletes again at the end.
 *
 * Usage:
 *   docker exec formulize-web-1 php /var/www/html/tests/element_code_files_test.php
 *
 * @package Formulize
 * @subpackage tests
 */

// Command-line / CI only. A web request just 404s - this ships in the repo and must never be a live endpoint.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

// The bootstrap reads these. Running from the command line there is no request to read them from.
// REQUEST_METHOD is deliberately GET: icms_core_Security::service() defines XOOPS_DB_PROXY on anything
// other than POST, and query() then refuses to write. This test has no business writing to the database,
// so it runs where it could not do so even by accident.
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_PORT'] = '80';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['REQUEST_URI'] = '/';

$root = dirname(__DIR__);
if (!is_file($root . '/mainfile.php')) {
    fwrite(STDERR, "Cannot find mainfile.php at: $root/mainfile.php - is Formulize installed?\n");
    exit(2);
}
include $root . '/mainfile.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/common.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/class/elements.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/class/baseClassForStaticContent.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/class/captionedContentElement.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/class/fullWidthContentElement.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/class/textElement.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/class/textareaElement.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/class/numberElement.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/class/derivedElement.php';

$GLOBALS['__pass'] = 0;
$GLOBALS['__fail'] = 0;

function check($label, $got, $want) {
    if ($got === $want) {
        $GLOBALS['__pass']++;
        return true;
    }
    $GLOBALS['__fail']++;
    printf("  FAIL %s\n       got:  %s\n       want: %s\n", $label, var_export($got, true), var_export($want, true));
    return false;
}

function ok($label, $condition, $detail = '') {
    if ($condition) {
        $GLOBALS['__pass']++;
        return true;
    }
    $GLOBALS['__fail']++;
    printf("  FAIL %s%s\n", $label, $detail === '' ? '' : "\n       $detail");
    return false;
}

// A handle no real element uses, so every file this test touches is its own.
define('CODE_FILE_TEST_HANDLE', 'zzcodefiletesthandle');
define('CODE_FILE_TEST_DIR', XOOPS_ROOT_PATH . '/modules/formulize/code/');

/**
 * The value the object is carrying, read the way the admin save path reads it: straight out of vars,
 * without going through getVar (which would substitute the file's contents and hide a blank).
 */
function storedValue($element, $key) {
    $raw = $element->vars['ele_value']['value'];
    $raw = is_array($raw) ? $raw : unserialize($raw);
    return (is_array($raw) AND isset($raw[$key])) ? (string) $raw[$key] : null;
}

function fileContents($filename) {
    clearstatcache(true, CODE_FILE_TEST_DIR . $filename);
    return is_file(CODE_FILE_TEST_DIR . $filename) ? file_get_contents(CODE_FILE_TEST_DIR . $filename) : null;
}

function newElement($class, $ele_type) {
    $element = new $class();
    $element->setVar('ele_type', $ele_type);
    $element->setVar('ele_handle', CODE_FILE_TEST_HANDLE);
    return $element;
}

// Every type that keeps code in a file, with the key of ele_value the code lives in, the name of the file
// it writes, a value that is code and a value that is not. Derived elements are always code, so for them
// "not code" is an empty formula.
$types = array(
    array(
        'label'    => 'captionedContent',
        'class'    => 'formulizeCaptionedContentElement',
        'ele_type' => 'captionedContent',
        'key'      => ELE_VALUE_STATICCONTENT_CONTENT,
        'file'     => 'captionedContent_' . CODE_FILE_TEST_HANDLE . '.php',
        'code'     => '<?php $value = "captioned content built by code";',
        'notCode'  => '<p>plain HTML with no code in it</p>',
    ),
    array(
        'label'    => 'fullWidthContent',
        'class'    => 'formulizeFullWidthContentElement',
        'ele_type' => 'fullWidthContent',
        'key'      => ELE_VALUE_STATICCONTENT_CONTENT,
        'file'     => 'fullWidthContent_' . CODE_FILE_TEST_HANDLE . '.php',
        'code'     => '<?php $value = "full width content built by code";',
        'notCode'  => '<h1>plain HTML with no code in it</h1>',
    ),
    array(
        'label'    => 'derived',
        'class'    => 'formulizeDerivedElement',
        'ele_type' => 'derived',
        'key'      => 0,
        'file'     => 'derived_' . CODE_FILE_TEST_HANDLE . '.php',
        'code'     => '<?php $value = $some_handle . " " . $another_handle;',
        'notCode'  => '',
    ),
    array(
        'label'    => 'text',
        'class'    => 'formulizeTextElement',
        'ele_type' => 'text',
        'key'      => ELE_VALUE_TEXT_DEFAULTVALUE,
        'file'     => 'text_' . CODE_FILE_TEST_HANDLE . '.php',
        'code'     => '<?php $default = "text default built by code";',
        'notCode'  => 'a plain default value',
    ),
    array(
        'label'    => 'number',
        'class'    => 'formulizeNumberElement',
        'ele_type' => 'number',
        'key'      => ELE_VALUE_TEXT_DEFAULTVALUE,
        'file'     => 'number_' . CODE_FILE_TEST_HANDLE . '.php',
        'code'     => '<?php $default = 40 + 2;',
        'notCode'  => '7',
    ),
    // textarea shares formulizeTextElement's setVar but keeps its default in key 0, which is also the key
    // the rule that used to live in formulizeElement::setVar looked at - the collision that deleted the
    // file in the same call that wrote it.
    array(
        'label'    => 'textarea',
        'class'    => 'formulizeTextareaElement',
        'ele_type' => 'textarea',
        'key'      => ELE_VALUE_TEXTAREA_DEFAULTVALUE,
        'file'     => 'textarea_' . CODE_FILE_TEST_HANDLE . '.php',
        'code'     => '<?php $default = "textarea default built by code";',
        'notCode'  => 'a plain default value',
    ),
);

// Nothing should be in the way before we start.
foreach ($types as $type) {
    if (is_file(CODE_FILE_TEST_DIR . $type['file'])) {
        fwrite(STDERR, "A file this test writes already exists: " . $type['file'] . " - refusing to run.\n");
        exit(2);
    }
}

echo "\n";
echo "=====================================================================\n";
echo " code is written to a file and kept in the object\n";
echo "=====================================================================\n";

foreach ($types as $type) {
    echo "\n" . $type['label'] . "\n";

    // The save path's first setVar: the admin's code arrives from the submitted form.
    $element = newElement($type['class'], $type['ele_type']);
    $element->setVar('ele_value', array($type['key'] => $type['code']));
    check('  the code is written to ' . $type['file'], fileContents($type['file']), $type['code']);
    check('  the object still holds the code', storedValue($element, $type['key']), $type['code']);

    // The save path's second setVar: upsertElementSchemaAndResources() loads the element again and sets
    // every property it was handed, and what it was handed is what the first pass left in the object.
    $second = newElement($type['class'], $type['ele_type']);
    $second->setVar('ele_value', $element->vars['ele_value']['value']);
    check('  the code survives the second setVar of the same save', fileContents($type['file']), $type['code']);
    check('  the object still holds the code after it', storedValue($second, $type['key']), $type['code']);

    // A caller that sets other keys of ele_value and leaves this one out is not saying "remove the code".
    $partial = newElement($type['class'], $type['ele_type']);
    $partial->setVar('ele_value', array(99 => 'some unrelated setting'));
    check('  an ele_value without the code key leaves the file alone', fileContents($type['file']), $type['code']);

    // Replacing the code with something that is not code does mean remove it.
    $cleared = newElement($type['class'], $type['ele_type']);
    $cleared->setVar('ele_value', array($type['key'] => $type['notCode']));
    check('  replacing the code with a plain value deletes the file', fileContents($type['file']), null);
    check('  the object holds the plain value', storedValue($cleared, $type['key']), $type['notCode']);
}

echo "\n";
echo "=====================================================================\n";
echo " on read, the file wins over the stored value\n";
echo "=====================================================================\n";

foreach ($types as $type) {
    echo "\n" . $type['label'] . "\n";

    // assignVars is how the handler builds an element out of a database row, so this is a value arriving
    // from the database, serialized, with no setVar involved.
    $element = new $type['class']();
    $element->assignVars(array(
        'ele_type'   => $type['ele_type'],
        'ele_handle' => CODE_FILE_TEST_HANDLE,
        'ele_value'  => serialize(array($type['key'] => 'the value stored in the database')),
    ));

    $onDisk = $type['code'] . ' // first version on disk';
    file_put_contents(CODE_FILE_TEST_DIR . $type['file'], $onDisk);
    $value = $element->getVar('ele_value');
    check('  getVar returns what is on disk, not what is stored', (string) $value[$type['key']], $onDisk);

    // Someone edits the file - in an editor, over SFTP, by deploying a change - without saving the element.
    $editedOnDisk = $type['code'] . ' // edited on disk since the last read';
    file_put_contents(CODE_FILE_TEST_DIR . $type['file'], $editedOnDisk);
    $value = $element->getVar('ele_value');
    check('  getVar returns the edit, not the version it read before', (string) $value[$type['key']], $editedOnDisk);

    // With no file there is nothing to prefer, so the stored value is what is left.
    unlink(CODE_FILE_TEST_DIR . $type['file']);
    clearstatcache(true, CODE_FILE_TEST_DIR . $type['file']);
    $value = $element->getVar('ele_value');
    check('  with no file, getVar falls back to the stored value', (string) $value[$type['key']], 'the value stored in the database');
}

echo "\n";
echo "=====================================================================\n";
echo " the database copy does not duplicate the file\n";
echo "=====================================================================\n";

// formulizeElementsHandler::insert() strips the code out of the copy of ele_value it is about to write, so
// the file stays the only place the code lives. The element object is untouched - insert() works on a local
// copy - which is what the first section above is about.
//
// Three things make this easy to get wrong, and all of them are silent:
//   - insert() calls $this->clearEleValueFileKeysForInsert(), and PHP resolves a PRIVATE method
//     non-virtually, so a private one on the base handler would run instead of the element type's override
//     and nothing would ever be stripped.
//   - what arrives is whatever cleanVars() produced: a serialized string when ele_value was the changed
//     property, the array itself when it was not. It goes straight to quoteString(), so handing back an
//     array where a string arrived writes the literal text "Array" into ele_value.
//   - stripping is only safe once the file is there to hold the code. Because what arrives is the stored
//     value whenever ele_value was NOT the property that changed, stripping on the shape of the content
//     alone would empty an element whose code is still only in the database, during a save that had
//     nothing to do with its content. See codeFileHasContentForElement() and the checks at the end.
$baseMethod = new ReflectionMethod('formulizeElementsHandler', 'clearEleValueFileKeysForInsert');
ok('the base handler\'s method is not private, so type handlers can override it', !$baseMethod->isPrivate(),
    'a private method here is never reached from insert(), and every override below is dead code');

// insert() is called on the GENERIC elements handler nearly everywhere in the system (the element save
// pages, element conversions, cloning, the ad hoc table forms), and only through
// upsertElementSchemaAndResources() on the type's own handler. The generic handler cannot know an element's
// type from its own construction, so insert() passes it the element and the base implementation hands off to
// the type's handler. Without that, the same element would store one thing saved from one tab and something
// else saved from another.
$genericHandler = xoops_getmodulehandler('elements', 'formulize');
ok('the generic handler is the one those paths use', get_class($genericHandler) === 'formulizeElementsHandler',
    'got ' . get_class($genericHandler));

// Called the way insert() calls it: from inside the base handler, which then reaches a protected method on a
// different object. PHP only allows that within one class family, so it is checked rather than assumed -
// getting it wrong is a fatal error on every element save, not merely a wrong value.
$asInsertCallsIt = Closure::bind(function ($handler, $ele_value, $element) {
    return $handler->clearEleValueFileKeysForInsert($ele_value, $element);
}, null, 'formulizeElementsHandler');

foreach ($types as $type) {
    // setVar writes the code file, which has to be there before the code can come out of the database copy.
    $element = newElement($type['class'], $type['ele_type']);
    $element->setVar('ele_value', array($type['key'] => $type['code']));
    try {
        $stripped = $asInsertCallsIt($genericHandler, serialize(array($type['key'] => $type['code'])), $element);
        $unpacked = is_string($stripped) ? unserialize($stripped) : $stripped;
        ok('  a ' . $type['label'] . ' handed to the generic handler comes back serialized', is_string($stripped), 'got a ' . gettype($stripped));
        check('  ...with the code stripped by its own type handler', is_array($unpacked) ? $unpacked[$type['key']] : null, '');
    } catch (Throwable $t) {
        $GLOBALS['__fail'] += 2;
        printf("  FAIL insert() cannot make the hand off for %s\n       %s: %s\n", $type['label'], get_class($t), $t->getMessage());
    }
}

// A type handler that does not override the method inherits the base copy, which must NOT hand off again -
// that would be an endless loop. Checked with a type that keeps nothing in a file, holding an element of a
// type that does, so a hand off would be visible as a stripped value.
$nonFileHandler = xoops_getmodulehandler('selectElement', 'formulize');
$captionedElement = newElement('formulizeCaptionedContentElement', 'captionedContent');
$untouched = serialize(array(ELE_VALUE_STATICCONTENT_CONTENT => '<?php $value = "still here";'));
$result = $asInsertCallsIt($nonFileHandler, $untouched, $captionedElement);
check('a type handler inheriting the base copy does not hand off again', $result, $untouched);

// The most travelled path of all, since most element types keep nothing in a file: the generic handler hands
// off once, to a handler that inherits the base copy, and it has to stop there rather than hand off to itself.
$selectElement = $nonFileHandler->create();
$selectElement->setVar('ele_type', 'select');
check('an element type that keeps nothing in a file is handed off once and left alone',
    $asInsertCallsIt($genericHandler, $untouched, $selectElement), $untouched);

// An element type with no handler of its own is left exactly as it arrived.
$noTypeElement = new formulizeElement();
$noTypeElement->setVar('ele_type', 'atypethatdoesnotexist');
check('an unknown element type is left alone', $asInsertCallsIt($genericHandler, $untouched, $noTypeElement), $untouched);
// And so is an element that was not passed at all, which is what an older caller would do.
check('no element passed means nothing to hand off to', $asInsertCallsIt($genericHandler, $untouched, null), $untouched);

foreach ($types as $type) {
    echo "\n" . $type['label'] . "\n";
    $handler = xoops_getmodulehandler($type['ele_type'] . 'Element', 'formulize');
    $method = new ReflectionMethod(get_class($handler), 'clearEleValueFileKeysForInsert');

    // The element is what names the code file, and the file has to exist before anything can be taken out
    // of the database copy, so setVar writes it first - as it would have done moments earlier on a real save.
    $element = newElement($type['class'], $type['ele_type']);
    $element->setVar('ele_value', array($type['key'] => $type['code']));

    // the shape insert() actually passes on an ordinary save
    $serialized = serialize(array($type['key'] => $type['code'], 99 => 'an unrelated setting'));
    $result = $method->invoke($handler, $serialized, $element);
    ok('  a serialized ele_value comes back serialized', is_string($result), 'got a ' . gettype($result));
    $unpacked = is_string($result) ? unserialize($result) : $result;
    check('  the code is not written to the database', $unpacked[$type['key']], '');
    check('  the other settings are untouched', $unpacked[99], 'an unrelated setting');

    // the shape it passes when ele_value was not the property that changed
    $result = $method->invoke($handler, array($type['key'] => $type['code']), $element);
    ok('  an array ele_value comes back as an array', is_array($result), 'got a ' . gettype($result));
    check('  the code is not written to the database', is_array($result) ? $result[$type['key']] : null, '');

    // a value that is not code is not in a file, so the database is the only place it can live
    $result = $method->invoke($handler, serialize(array($type['key'] => $type['notCode'])), $element);
    $unpacked = is_string($result) ? unserialize($result) : $result;
    check('  a value that is not in a file is left alone', $unpacked[$type['key']], $type['notCode']);

    // a brand new element can have an ele_value that is not a serialized array at all
    check('  an unreadable ele_value is handed back untouched', $method->invoke($handler, '', $element), '');

    // THE FILE HAS TO BE THERE TO TAKE THE CODE OUT OF THE DATABASE. Code can be in ele_value with no file:
    // patch 001 wrote the files but left the database copies alone, cloneForm() copies element rows with raw
    // SQL and no files, and an install whose code directory did not survive a deploy is in the same place.
    // Those elements work, because getVar falls back to the stored value. Since insert() is handed the stored
    // value whenever ele_value was not the property that changed, blanking it here would empty them on a save
    // that had nothing to do with the content - the Display tab, a reorder - with nothing holding the code.
    unlink(CODE_FILE_TEST_DIR . $type['file']);
    clearstatcache(true, CODE_FILE_TEST_DIR . $type['file']);
    $result = $method->invoke($handler, $serialized, $element);
    $unpacked = is_string($result) ? unserialize($result) : $result;
    check('  code with no file to hold it stays in the database', $unpacked[$type['key']], $type['code']);

    // an empty file is not holding anything either - a write that failed part way leaves one
    file_put_contents(CODE_FILE_TEST_DIR . $type['file'], '');
    $result = $method->invoke($handler, $serialized, $element);
    $unpacked = is_string($result) ? unserialize($result) : $result;
    check('  code whose file is empty stays in the database', $unpacked[$type['key']], $type['code']);

    // and with no element there is no way to name the file, so there is nothing to show the code is held
    unlink(CODE_FILE_TEST_DIR . $type['file']);
    clearstatcache(true, CODE_FILE_TEST_DIR . $type['file']);
    $result = $method->invoke($handler, $serialized);
    $unpacked = is_string($result) ? unserialize($result) : $result;
    check('  with no element to name the file, the code stays in the database', $unpacked[$type['key']], $type['code']);

    // and the same thing again on what cleanVars() really produces, rather than on an assumption about
    // what it produces, carried through to the quoteString() call that insert() makes with it
    $element = newElement($type['class'], $type['ele_type']);
    $element->setVar('ele_value', array($type['key'] => $type['code']));
    $element->cleanVars();
    $fromCleanVars = $element->cleanVars['ele_value'];
    ok('  cleanVars hands insert() a string', is_string($fromCleanVars), 'got a ' . gettype($fromCleanVars));
    $stripped = $method->invoke($handler, $fromCleanVars, $element);
    $quoted = $GLOBALS['xoopsDB']->quoteString($stripped);
    ok('  quoteString writes it as a value, not as the word "Array"', is_string($quoted) AND strstr($quoted, 'Array') === false, 'quoteString produced: ' . var_export($quoted, true));
    $unpacked = is_string($stripped) ? unserialize($stripped) : $stripped;
    check('  and what reaches the database has no code in it', is_array($unpacked) ? $unpacked[$type['key']] : null, '');
    check('  while the file still has it', fileContents($type['file']), $type['code']);
}

echo "\n";
echo "=====================================================================\n";
echo " nothing is left behind\n";
echo "=====================================================================\n";

// CI collects modules/formulize/code/ as a build artifact, so a file left here would end up in it.
foreach ($types as $type) {
    if (is_file(CODE_FILE_TEST_DIR . $type['file'])) {
        unlink(CODE_FILE_TEST_DIR . $type['file']);
    }
    ok('  ' . $type['file'] . ' is gone', !is_file(CODE_FILE_TEST_DIR . $type['file']));
}

echo "\n";
printf("RESULT: %d passed, %d failed\n", $GLOBALS['__pass'], $GLOBALS['__fail']);
exit($GLOBALS['__fail'] === 0 ? 0 : 1);
