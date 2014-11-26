<a name="2.0.3"></a>
# 2.0.3 (2014-11-26)

### Features

- **db\orm\Repository:** add FETCH_* mode to insert/update/delete operations

### Bug Fixes

- **db\orm:** fix support for NULL values



<a name="2.0.2"></a>
# 2.0.2 (2014-11-20)

### Features

- **cache\SqliteCache:** add optional write buffer, INSERT queries are delayed till shutdown

### Performance improvements

- **Kernel cache:** now use SqliteCache write buffer. This should resolve the initial big bang when kernel cache is empty, many concurrent requests arrive, causing sqlite insert queries overlapping.



<a name="2.0.1"></a>
# 2.0.1 (2014-11-18)

### Bug Fixes

- **db\orm:** fix support for NULL values
  ([a626f7d](https://github.com/Metadigit/Core/commit/a626f7ddcfd94ffec268e0bb6ac992c00373c334),
   [#1](https://github.com/Metadigit/Core/issues/1))

### Features

- **db\Query:** add support for GROUP BY, HAVING, WITH ROLLUP
- **db\orm\Repository:** add support for FETCH_JSON mode



<a name="2.0.0"></a>
# 2.0.0 (2014-07-17) #

Initial open source release on GitHub
