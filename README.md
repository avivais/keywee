# Keywee og scraper
## Examples
```bash
$ curl http://avi.vais.hiring.keywee.io/stories/16046247853441 # Should return metadata
$ curl http://avi.vais.hiring.keywee.io/stories/111 # Non-existing id
$ curl -X POST "http://avi.vais.hiring.keywee.io/stories?url=https%3A%2F%2Fedition.cnn.com%2F2020%2F11%2F05%2Fpolitics%2Fdonald-trump-election-2020%2Findex.html" # Already existing URL (In the canonical form)
$ curl -X POST "http://avi.vais.hiring.keywee.io/stories?url=https%3A%2F%2Fwww.msnbc.com%2Fmsnbc%2Fwatch%2t-in-georgia-opportunities-for-biden-in-pennsylvania-95412805908"
```
