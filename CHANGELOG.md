<a name="2.0.7"></a>
# 2.0.7 (2016-03-11)

### Features
- **db\orm\Repository:** add support for NULL page/pageSize, retrieving all records
- **db\Query:** add method execInsertUpdate() for "INSERT ... ON DUPLICATE KEY UPDATE ..." statements

### Bug Fixes
- **db\Query:** fix params parsing



<a name="2.0.6"></a>
# 2.0.6 (2015-05-25)

### Features
- **db\orm\EntityTrait:** perform type casting inside __construct() and __set()
- **util\Validator:** new validators: date, datetime, year
- **util\Validator:** add support for empty values: @validate(empty)

### Breaking changes
- **db\orm\Repository:** new API insert(mixed $id, array|object $data, $validate, $fetchMode, $fetchSubset)
- **db\orm\Repository:** new API update(mixed $id, array|object $data, $validate, $fetchMode, $fetchSubset)



<a name="2.0.5"></a>
# 2.0.5 (2015-05-07)

### Features
- **db\orm\Repository:** add support for validation subsets via @orm-validate-subset

### Breaking changes
- **db\orm\Repository:** rename @orm-subset to @orm-fetch-subset



<a name="2.0.4"></a>
# 2.0.4 (2015-03-02)

### Features
- **Exception:** new constructor with $code, $message and optional $data
- **db\orm\Repository:** insert/update operations now throw db\orm\Exception 500 on validation errors
- **db\orm\Repository:** add support for custom validate() function



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
