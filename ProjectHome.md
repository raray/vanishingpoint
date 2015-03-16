# About #

Vanishing Point is a toolkit that enables the programmer to implement plugins for different file types to be optimized. The class's main role in optimization is managing the files and calling the appropriate render(). Plugins are based upon the VP\_Module interface and should adhere to the standards defined in the VP\_Module documentation.

Vanishing Point initially debuts with an automatic JavaScript file optimization plugin, which makes use Nicolas Martin's JavaScriptPacker class as well as other JavaScript optimization techniques.

If all optimization features in Vanishing Point are used, a user should notice a dramatic shift in their [YSlow](http://developer.yahoo.com/yslow/) scores, and should bring most 'grades' to an **A**.

### Example ###

The following is an example of using Vanishing Point. For the sake of demonstration, we have used the VP\_JS Javascript Plugin for Vanishing Point

```
<?php
//include requied classes
require_once('class.VanishingPoint.php');
require_once('class.VP_JS.php');

//create the new Vanishing Point object
$vp = new VanishingPoint();

//add a module of type VP_JS to the group 'example' with version 1-0-0
$vp->addModule(new VP_JS("example.js","1-0-0"),"example");

$output = $vp->render("example");

echo $output['example'];
?>
```

# Road Map #

Currently Vanishing Point is in the early stages of its development. Our TODO list:

  * Use [filemtime()](http://us.php.net/manual/en/function.filemtime.php) rather than versions
  * Run more formal benchmarks
  * Eventually 100% code coverage