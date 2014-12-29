#Subdivx Download
CLI tool that eases the process of searching and downloading subtitles from the subdivx.com repository.

### Installation
You need to have composer installed in order to pull this tool.
```bash
$ composer global require "p4bloch/subdivx:1.*"
```

### Usage
```bash
$ subdivx download
```
and then interactively search for the subtitle, or you can pass the search term directly as an argument:
```bash
$ subdivx download 'my tv show s01e01'
```
You can also pass a second parameter to the command, which will act as a filter to the results. Very handy when you need subtitles for a specific version.
```bash
$ subdivx download 'my tv show s01e01' web-dl
$ subdivx download 'my tv show s01e01' lol
$ subdivx download 'my tv show s01e01' argenteam
```