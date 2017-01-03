# DownloadLinkGeneratorForDropbox
Create list of files and their download links in a Dropbox folder. Available at: https://yasirkula.net/dropbox/downloadlinkgenerator/

##How does it work?

Using the Dropbox API, after user authenticates the app, a number of queries are sent to the path user provides.

- If path leads to a file and the file is shared, download link to that file is returned
- If path leads to a directory, download links for any shared files in that directory and any directories under it (recursive) are returned

If auto sharing is enabled, any unshared file at the path will automatically be shared publicly
