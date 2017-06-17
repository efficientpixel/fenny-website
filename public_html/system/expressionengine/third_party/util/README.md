util
====

ExpressionEngine plugin which allows to call few string processing php functions from your template. Has few other useful methods too


<strong>func</strong><br>
Execute any php function from template, You can pass function argument/constant too<br>
Example:
```
{exp:util:func function="htmlentities" params="ENT_QUOTES|'UTF-8'"}A 'quote' is <b>bold</b>{/exp:util:func}
{exp:util:func function="strip_tags" params="'<p><a>'"}<p>Test paragraph.</p><!-- Comment --> <a href="#fragment">Other text</a>{/exp:util:func}
{exp:util:func function="strlen" data="Get string length"}
```

<strong>escape</strong><br>
Convert special characters to HTML entities<br>
Example:
```
{exp:util:escape data="<a href='test'>Test</a>"}
```

<strong>join</strong><br>
Join a string by a specific glue<br>
Example: 
```
{exp:util:join data="1,2,3,4" glue="|"}
```

<strong>first</strong><br>
Get first character of a string<br>
Example:
```
{exp:util:first data="1,2,3,4"}
{exp:util:first data="1234"}
```

<strong>last</strong><br>
Get last character of a string<br>
Example:
```
{exp:util:last data="1,2,3,4"}
{exp:util:last data="1234"}
```