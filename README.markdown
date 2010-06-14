Radius for PHP
==============

This is my intent to port the [Radius][1] template language to PHP.

Radius is a powerful tag-based template language inspired by the template languages
used in [MovableType][2] and [TextPattern][3]. It uses tags similar to XML, but can
be used to generate any form of plain text (HTML, e-mail, etc...).

For PHP 5.3 or higher.

Usage
-----

	require_once '/path/to/radius/lib/Radius/Autoloader.php';
	Radius\Autoloader::register();

	// Create a context and define some tags
	$context = new Radius\Context();
	$context->defineTags(array(
		'hello' => function () {
			return 'Hello world';
		},
		'repeat' => function ($tag) {
			$number = $tag->getAttr('times', 1);
			$result = '';
			for ($i = 0; $i < $number; ++$i) {
				$result .= $tag->expand();
			}
			return $result;
		},
		));

	// Create a parser to parse tags that begin with 'r:'
	$parser = new Radius\Parser($context, array('tag_prefix' => 'r'));

	// Parse and outputs the result
	echo $parser->parse("A small example:\n<r:repeat times='3'>* <r:hello />!\n</r:repeat>");

Output:

    A small example:
    * Hello world!
    * Hello world!
    * Hello world!

Development
-----------

This is not complete. If you feel interested and want to contribute, please contact me.

[1]: http://github.com/jlong/radius
[2]: http://www.movabletype.org/
[3]: http://www.textpattern.com/
