<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<html>
<head>
	<title>Test Set Operations</title>
</head>

<body>



<?php
define("RDFAPI_INCLUDE_DIR", "./../api/");
include(RDFAPI_INCLUDE_DIR . "RdfAPI.php");

echo "<h3>1. Generate and show two MemModels</h3>";

// Create empty MemModel
$model = new MemModel();
$model->setbaseURI("http://www.bizer.de");

$model2 = new MemModel();
$model2->setbaseURI("http://www.bizer.de/zwei");

// Create nodes and add statements to models
$myhomepage = new Resource("http://www.bizer.de/welcome.html");
$creator = new Resource("http://purl.org/dc/elements/1.1/creator");
$me = new Resource("mailto:chris@bizer.de");
$model->add(new Statement($myhomepage, $creator, $me));
$model2->add(new Statement($myhomepage, $creator, $me));

$creation_date = new Resource("http://www.example.org/terms/creation-date");
$August16 = new Literal("August 16, 2002");
$model->add(new Statement($myhomepage, $creation_date, $August16));
$model2->add(new Statement($myhomepage, $creation_date, $August16));

$language = new Resource("http://www.example.org/terms/language");
$deutsch = new Literal("Deutsch", "de");
$model->add(new Statement($myhomepage, $language, $deutsch));

$name = new Resource("http://www.example.org/terms/Name");
$chrisbizer = new Literal("Chris Bizer");
$model2->add(new Statement($me, $name, $chrisbizer));

// Output as Table
echo "<h5>Model 1</h5>";
$model->writeAsHtmlTable();
echo "<h5>Model 2</h5>";
$model2->writeAsHtmlTable();
echo "<P>";

echo "<h3>2. Make some tests</h3>";
echo "Test: Model 2 contains any statements from model 1 :".$model2->containsAny($model)."<p>"; 
echo "Test: Model 1 contains any statements from model 2 :".$model->containsAny($model2)."<p>"; 
echo "Test: Model 2 contains all statements from model 1 :".$model2->containsAll($model)."<p>"; 
echo "Test: Model 1 contains all statements from model 2 :".$model->containsAll($model2)."<p>"; 
echo "Test: Model 1 equals model 2 :".$model->equals($model2)."<p>"; 

echo "<h3>3. Unite model 1 and model 2</h3>";
$model3 =& $model->unite($model2);
$model3->writeAsHtmlTable();

echo "<h3>4. Intersect model 1 and model 2</h3>";
$model4 =& $model->intersect($model2);
$model4->writeAsHtmlTable();

echo "<h3>5. Substract model 2 from model 1</h3>";
$model5 =& $model->subtract($model2);
$model5->writeAsHtmlTable();

echo "<h3>6. Reify model 1</h3>";
$model6 =& $model->reify();
$model6->writeAsHtmlTable();

?>


</body>
</html>
