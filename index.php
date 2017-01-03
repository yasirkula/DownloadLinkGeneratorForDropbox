<!doctype html>
<html>
<head>
	<title>Dropbox Download Link Generator</title>
	<script src="https://unpkg.com/dropbox/dist/Dropbox-sdk.min.js"></script>
</head>
<body onload="pageLoaded()">
	<?php
	session_start();
	
	if( isset($_GET["reauthenticate"]) && $_GET["reauthenticate"] == 1 )
	{
		unset( $_SESSION["downloadlinkgeneratordb_token"] );
	}
	
	if( isset($_GET["access_token"]) && $_GET["access_token"] )
	{
		$_SESSION["downloadlinkgeneratordb_token"] = $_GET["access_token"];
	}
	
	$tokenIsSet = 0;
	?>
	<input id="authtoken" type="hidden" value="<?php 
	if( isset($_SESSION["downloadlinkgeneratordb_token"]) && $_SESSION["downloadlinkgeneratordb_token"] )
	{
		$tokenIsSet = 1;
		echo $_SESSION["downloadlinkgeneratordb_token"];
	}
	?>" />
	
	<pre>Have any questions? Drop me a mail at <a href="mailto:yasirkula@gmail.com">yasirkula@gmail.com</a></pre></br>
	
	<h3>How does it work?</h3>
	Using the Dropbox API, after you authenticate this app, a number of queries are sent to the path you provide.</br></br>
	
	- If path leads to a file and the file is shared, download link to that file is returned</br>
	- If path leads to a directory, download links for any shared files in that directory and any directories under it (recursive) are returned</br></br>
	
	If auto sharing is enabled, any unshared file at the path will automatically be shared publicly.</br>
	Note that using this app on a huge folder might yield API errors for sending too many requests to the server</br></br>
	
	<?php if( $tokenIsSet == 1 ) { ?>
	Click this button when e.g. you login as another Dropbox user or if app doesn't seem to work: <button onclick="revokeSession()">Reauthenticate</button></br></br>
	
	<h3>Options</h3>
	Path to file/folder: <input type="text" name="dropboxPath" id="dropboxPath"></br>
	<input type="checkbox" name="cacheLinks" id="cacheLinks"> Cache shared links for better performance (<u>select this option</u> unless you enter the path of a single file or a tiny directory)</br>
	<input type="checkbox" name="autoShare" id="autoShare"> Automatically share unshared file(s) publicly (undoing this might be nightmare for large directories!)</br></br>
	
	<button onclick="executeQuery()">Go!</button>
	<?php } else { ?>
	Click here to authenticate this app on Dropbox first: <a href="" id="authlink" class="button">Authenticate</a>
	<?php } ?>
	
	</br>
	
	<pre id="status"></pre></br>
	<pre id="result"></pre>
	<pre id="error" style="color: red; text-style: bold;"></pre>
	
	<script>
	var CLIENT_ID = 'YOUR_APP_CLIENT_ID';
	var dbx;
	
	var statusText;
	var resultText;
	var errorText;
	
	var waitingFileCount = 0;
	var waitingFolderCount = 0;
	
	var autoShareFiles = false;
	
	var cacheSharedLinks = true;
	var cachedShareLinks = {};
	
	var concurrentFileOpCount = 5;
	var waitingFilesStack = [];
	
	var relativePathStartIndex = 0;
	
	function revokeSession()
	{
		window.location.href = 'https://yasirkula.net/dropbox/downloadlinkgenerator/?reauthenticate=1';
	}
	
	function getAccessTokenFromHash() 
	{
		var result = parseQueryString(window.location.hash).access_token;
		if( !result )
			return "";

		return result;
	}

	function getAccessToken() 
	{
		var val = document.getElementById('authtoken').value;
		if( !val )
			return "";

		return val;
	}

	function isAuthenticated() 
	{
		return getAccessToken().length > 0;
	}

	function processFolder(m_path, cursor)
	{
		onWaitForFolder( true );
		
		if( cursor == null )
		{
			dbx.filesListFolder({ path: m_path, recursive: true }).then(function(response) {
				processFolderInner( m_path, response );
				onWaitForFolder( false );
			}).catch(function(error) {
				errorText.innerHTML += "Error code " + error.status + ": " + error.error + "(" + error.message + ")\r\n";
				onWaitForFolder( false );
			});
		}
		else
		{
			dbx.filesListFolderContinue({ cursor: cursor }).then(function(response) {
				processFolderInner( m_path, response );
				onWaitForFolder( false );
			}).catch(function(error) {
				errorText.innerHTML += "Error code " + error.status + ": " + error.error + "(" + error.message + ")\r\n";
				onWaitForFolder( false );
			});
		}
	}
	
	function processFolderInner(m_path, response)
	{
		if( response.has_more )
		{
			processFolder( m_path, response.cursor );
		}
			
		var items = response.entries;
		items.forEach(function(item) 
		{
			if( item[".tag"] != 'folder' )
			{
				if( waitingFileCount < concurrentFileOpCount )
					filePrintShareLink( item.path_display, item.rev );
				else
					waitingFilesStack.push( { _path: item.path_display, _rev: item.rev } );
			}
		});
	}
	
	function filePrintShareLink(m_path, rev)
	{
		if( cacheSharedLinks )
		{
			if( cachedShareLinks[rev] !== undefined )
				resultText.innerHTML += getDownloadLink( m_path, cachedShareLinks[rev] );
			else if( autoShareFiles )
			{
				onWaitForFile( true );
				fileCreateShareLink( m_path );
			}
		}
		else
		{
			onWaitForFile( true );
			
			dbx.sharingListSharedLinks({ path: m_path, direct_only: true }).then(function(response) {
				if( response.links.length > 0 )
				{
					resultText.innerHTML += getDownloadLink( m_path, response.links[0].url );
					onWaitForFile( false );
				}
				else if( autoShareFiles )
				{
					fileCreateShareLink( m_path );
				}
				else
				{
					onWaitForFile( false );
				}
			}).catch(function(error) {
				errorText.innerHTML += "Error code " + error.status + ": " + error.error + "(" + error.message + ")\r\n";
				onWaitForFile( false );
			});
		}
	}
	
	function fileCreateShareLink(m_path)
	{
		dbx.sharingCreateSharedLinkWithSettings({ path: m_path }).then(function(response) {
			resultText.innerHTML += getDownloadLink( m_path, response.url );
			onWaitForFile( false );
		}).catch(function(error) {
			errorText.innerHTML += "Error code " + error.status + ": " + error.error + "(" + error.message + ")\r\n";
			onWaitForFile( false );
		});
	}
	
	function onWaitForFolder( isWaiting )
	{
		if( isWaiting )
		{
			waitingFolderCount++;
		}
		else
		{
			waitingFolderCount--;
		}
		
		onStatusUpdate();
	}
	
	function onWaitForFile( isWaiting )
	{
		if( isWaiting )
		{
			waitingFileCount++;
		}
		else
		{
			waitingFileCount--;
			
			if( waitingFilesStack.length > 0 )
			{
				var fileToPrint = waitingFilesStack.shift();
				filePrintShareLink( fileToPrint._path, fileToPrint._rev );
			}
		}
		
		console.log( "Files in progress: " + waitingFileCount );
		
		onStatusUpdate();
	}
	
	function onStatusUpdate()
	{
		if( waitingFileCount != 0 || waitingFolderCount != 0 )
			statusText.innerHTML = "Please wait...";
		else
			statusText.innerHTML = "";
	}
	
	function getDownloadLink(m_path, shareLink)
	{
		if( shareLink.endsWith( "?dl=0" ) )
			shareLink = shareLink.substring( 0, shareLink.length - 1 ) + "1";
		else
			shareLink = shareLink + "?dl=1";
		
		if( m_path[0] == '/' && relativePathStartIndex == 0 )
			m_path = m_path.substring( 1 );
		else
			m_path = m_path.substring( relativePathStartIndex );
		
		return m_path + " " + shareLink + "\r\n";
	}

	function executeQuery()
	{
		if( waitingFileCount != 0 || waitingFolderCount != 0 )
		{
			alert( "Another query is in process. If you think it got stuck, please refresh the page." );
			return;
		}
		
		cacheSharedLinks = document.getElementById('cacheLinks').checked;
		autoShareFiles = document.getElementById('autoShare').checked;
		
		statusText = document.getElementById('status');
		resultText = document.getElementById('result');
		errorText = document.getElementById('error');
		
		resultText.innerHTML = "";
		errorText.innerHTML = "";
		
		dbx = new Dropbox({ accessToken: getAccessToken() });
		
		if( cacheSharedLinks )
		{
			statusText.innerHTML = "Caching shared links to reduce API calls...";
			onCacheSharedLinks( null );
		}
		else
		{
			executeQueryCommon();
		}
	}
	
	function onCacheSharedLinks(cursor)
	{
		var params = {};
		if( cursor != null )
			params.cursor = cursor;
		
		dbx.sharingListSharedLinks( params ).then(function(response) {
			if( response.has_more )
				onCacheSharedLinks( response.cursor );
		
			var links = response.links;
			links.forEach(function(link) 
			{
				cachedShareLinks[link.rev] = link.url;
			});
			
			if( !response.has_more )
				executeQueryCommon();
		}).catch(function(error) {
			errorText.innerHTML += "Error code " + error.status + ": " + error.error + "(" + error.message + ")\r\n";
			statusText.innerHTML = "";
		});
	}
	
	function executeQueryCommon()
	{
		var m_path = document.getElementById('dropboxPath').value;
		if( m_path.length > 0 && m_path[0] != '/' )
			m_path = '/' + m_path;
			
		if( m_path.length > 0 && m_path[m_path.length - 1] == '/' )
			m_path = m_path.substring( 0, m_path.length - 1 );
		
		if( m_path.length > 0 )
		{
			onWaitForFolder( true );
			dbx.filesGetMetadata({ path: m_path }).then(function(response) {
				if( response[".tag"] == 'file' )
				{
					filePrintShareLink( m_path, response.name, "1a2b3c" );
				}
				else
				{
					relativePathStartIndex = m_path.length + 1;
					processFolder( m_path + "/", null );
				}
				
				onWaitForFolder( false );
			}).catch(function(error) {
				errorText.innerHTML += "Error code " + error.status + ": " + error.error + "(" + error.message + ")\r\n";
				onWaitForFolder( false );
			});
		}
		else
		{
			relativePathStartIndex = 0;
			processFolder( "", null );
		}
	}
	
	function pageLoaded()
	{
		if( !isAuthenticated() )
		{
			var tokenHash = getAccessTokenFromHash();
			if( tokenHash.length > 0 )
			{
				window.location.href = 'https://yasirkula.net/dropbox/downloadlinkgenerator/?access_token=' + tokenHash;
			}
			else
			{
				var dbx = new Dropbox({ clientId: CLIENT_ID });
				var authUrl = dbx.getAuthenticationUrl('https://yasirkula.net/dropbox/downloadlinkgenerator/');
				document.getElementById('authlink').href = authUrl;
			}
		}
	}
	
	function parseQueryString(str) 
	{
		var ret = Object.create(null);

		if (typeof str !== 'string')
			return ret;

		str = str.trim().replace(/^(\?|#|&)/, '');

		if (!str)
			return ret;

		str.split('&').forEach(function (param) {
			var parts = param.replace(/\+/g, ' ').split('=');
			// Firefox (pre 40) decodes `%3D` to `=`
			// https://github.com/sindresorhus/query-string/pull/37
			var key = parts.shift();
			var val = parts.length > 0 ? parts.join('=') : undefined;

			key = decodeURIComponent(key);

			// missing `=` should be `null`:
			// http://w3.org/TR/2012/WD-url-20120524/#collect-url-parameters
			val = val === undefined ? null : decodeURIComponent(val);

			if (ret[key] === undefined) {
				ret[key] = val;
			} else if (Array.isArray(ret[key])) {
				ret[key].push(val);
			} else {
				ret[key] = [ret[key], val];
			}
		});

		return ret;
	}
	</script>
</body>
</html>
