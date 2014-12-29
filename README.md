#Subdivx Download
CLI tool that eases the process of searching and downloading subtitles from the subdivx.com repository.

### Dependencies
This project depends upon the **unrar** and **unzip** tools to extract the subtitle from the downloaded file. On OS X, [Homebrew](http://brew.sh/) is your friend to install them.

### Installation
You need to have [Composer](https://getcomposer.org/doc/00-intro.md), the PHP Package Manager, installed on your machine in order to use this tool. Once you have composer, you can install this package globally, so it can be accessed from any directory.
```bash
$ composer global require "p4bloch/subdivx:1.*"
```

### Usage
**Note**: You need to add *~/.composer/vendor/bin* to your PATH. On OS X, simply edit (or create) ~/.bash_profile and add this line `export PATH=$PATH:~/.composer/vendor/bin`. Open up a new terminal and you should be good to go.

In order to search for subtitles, simply type `subdivx` into your terminal and you'll be prompted to enter the subtitle you are looking for. Search results are ordered by date, descending, so new (and presumably better) subtitles appear first.

Alternatively, you can pass the search term as a parameter:
```bash
$ subdivx download 'my tv show s01e01'
```
You can also pass a second parameter to the command, which will act as a filter to the results. This is very handy when you need subtitles for a specific version.
```bash
$ subdivx download 'my tv show s01e01' web-dl
$ subdivx download 'my tv show s01e01' lol
$ subdivx download 'my tv show s01e01' argenteam
```
### Update
In order to update this package, run
```bash
$ composer global update
```