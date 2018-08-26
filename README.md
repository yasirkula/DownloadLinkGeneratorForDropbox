# Download Link Generator For Dropbox
Create list of files and their download links in a Dropbox folder. Available at: https://yasirkula.net/dropbox/downloadlinkgenerator/

## How does it work?

Using the Dropbox API, after user authenticates the app, a number of queries are sent to the path user provides.

- If path leads to a file and the file is shared, download link to that file is returned
- If path leads to a directory, download links for any shared files in that directory and any directories under it (recursive) are returned

If auto sharing is enabled, any unshared file at the path will automatically be shared publicly.

## Why would I want to use this app?

Say you have a large number of files and you want to get a download link for each of these files. You decide to host your files on Dropbox. The thing is, you don't want to spend so much time sharing each file separately and copying their download links manually. 

With this app, you can enable the "auto share" option and enter the path to the Dropbox directory that contains your files and get a list of download links in the following format (one file per line): {File relative path} {Download url}

Now you can write a simple script to fetch the download links from that list and use it however you want.
