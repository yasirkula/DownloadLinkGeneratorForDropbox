# Download Link Generator For Dropbox™

This is the source code of *Download Link Generator For Dropbox™* extension: https://yasirkula.net/dropbox/downloadlinkgenerator/

It lets users generate a list of their files with their download links in a Dropbox™ folder. Feel free to use this repository as a reference if you are creating your own Dropbox™ extension with similar functionality.

**[Support the Developer ☕](https://yasirkula.itch.io/unity3d)**

## How does the extension work?

Using the [Dropbox API](https://www.dropbox.com/developers/documentation/http/documentation), after user authenticates the extension and enters the path of a file/folder, a number of queries are made as follows:

- If path leads to a file and the file is publicly shared, download link of that file is returned
- If path leads to a folder, download links for all publicly shared files in that folder and any folders underneath it (recursive) are returned

If "*Auto share*" feature is enabled, any non-public (unshared) files in the folder will be publicly shared automatically.

## Why would I want to use this extension?

Say you have a large number of files on Dropbox™ and you want to get a download link for each of these files. The thing is, you don't want to spend so much time sharing each file one by one and copying their download links manually.

Instead, you can tell this extension the path of the Dropbox™ folder that holds your files (with "*Auto share*" feature enabled) and the extension will traverse that folder, generating a list of the download links in the following format (one file per line): `{File relative path} {File's download url}`
