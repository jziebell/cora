<h1>Cora</h1>
A lightweight extendable PHP API core. For additional documentation, examples, and other resources, visit <a href="http://www.jonziebell.com/cora" target="_blank">jonziebell.com/cora</a>.

<h2>In this repository</h2>
This repository contains <em>only</em> those files necessary to install and use Cora. Additional examples can be found on the website.
<ul>
  <li>/cora - Cora core classes</li>
  <li>index.php - The only publicly accessed file for the API</li>
</ul>

<h2>Installation</h2>
<ol>
  <li>Download this project and copy it into your API directory (/var/www/api/v1/ for example)</li>
  <li>Edit the settings at the beginning of cora/cora.php</li>
  <li>Execute cora/cora.sql on your database.</li>
  <li>You're done. If your configuration is correct you can now POST to the index.php file and use the API.</li>
</ol>

<hr/>

<em>Cora is developed and maintained by Jon Ziebell.</em>

<!-- 
<h2>Requirements</h2>
<ul>
  <li>PHP 5.3.7+ for <a href="http://php.net/manual/en/function.crypt.php" target="_blank">crypt</a> support for password hashing using CRYPT_BLOWFISH.</li>
  <li>MySQL 5.5+</li>
</ul>

<h2>Error Codes</h2>
<ul>
  <li>1000 - API Key is required.</li>
  <li>1001 - Resource is required.</li>
  <li>1002 - Method is required.</li>
  <li>1003 - Invalid API key.</li>
  <li>1004 - Session is expired.</li>
  <li>1005 - Rate limit reached.</li>
  <li>1006 - Request must be sent over HTTPS.</li>
  <li>1007 - Requested resource is not mapped.</li>
  <li>1008 - Requested method is not mapped.</li>
  <li>1009 - Method does not exist.</li>
  <li>1100 - Resource item not found.</li>
  <li>1200 - Could not connect to database.</li>
  <li>1201 - Failed to start database transaction.</li>
  <li>1202 - Failed to commit database transaction.</li>
  <li>1203 - Failed to rollback database transaction.</li>
  <li>1204 - Query identifier is invalid.</li>
  <li>1205 - Delete queries are not allowed.</li>
  <li>1206 - Duplicate database entry.</li>
  <li>1207 - Database query failed.</li>
  <li>1208 - Updates require at least one attribute.</li>
  <li>1209 - Must use the same keys across all items.</li>
  <li>1300 - Resource item not found.</li>
</ul>

<h2>Return values</h2>

If an error occurred:
<pre>
success   => Bool
request*  => Array
data      =>
  error_message => String
  error_code    => Int
  error_file*   => String
  error_line*   => Int
  error_trace*  => Array
</pre>

If success:
<pre>
success   => Bool
request*  => Array
data      => [Defined by API method]
</pre>

\* Only if debug is enabled.

 -->
