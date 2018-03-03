# INSERT

> The modifier `DELAYED` is not supported from version 5.7.

```
INSERT [LOW_PRIORITY | DELAYED | HIGH_PRIORITY] [IGNORE]
    [INTO] tbl_name
    [PARTITION (partition_name [, partition_name] ...)]
    [(col_name [, col_name] ...)]
    {VALUES | VALUE} (value_list) [, (value_list)] ...
    [ON DUPLICATE KEY UPDATE assignment_list]

INSERT [LOW_PRIORITY | DELAYED | HIGH_PRIORITY] [IGNORE]
    [INTO] tbl_name
    [PARTITION (partition_name [, partition_name] ...)]
    SET assignment_list
    [ON DUPLICATE KEY UPDATE assignment_list]

INSERT [LOW_PRIORITY | HIGH_PRIORITY] [IGNORE]
    [INTO] tbl_name
    [PARTITION (partition_name [, partition_name] ...)]
    [(col_name [, col_name] ...)]
    SELECT ...
    [ON DUPLICATE KEY UPDATE assignment_list]

value:
    {expr | DEFAULT}

value_list:
    value [, value] ...

assignment:
    col_name = value

assignment_list:
    assignment [, assignment] ...
```

INSERT INTO tbl_name VALUES( val1, val2, ... );

INSERT INTO tbl_name ( col1, col2 ) VALUES ( val1, val2 );

```
INSERT INTO tbl_name ( col1, col3 ) VALUES ( :val1, :val2 );
```

INSERT INTO tbl_name ( col1, col2 ) VALUES ( val1, col1 * 2 );

INSERT INTO tbl_name ( a, b, c ) VALUES ( 1, 2, 3 ), ( 4, 5, 6 ), ( 7, 8, 9 );

INSERT INTO tbl_name ( a, b, c, d ) VALUES ( 1, 2, 3, 4 ) ON DUPLICATE KEY UPDATE c = c + 1, d = 5;

INSERT INTO tbl_name ( a, b, c ) VALUES ( 1, 2, 3 ) ON DUPLICATE KEY UPDATE c = VALUES( a ) + VALUES( b ); 

INSERT INTO t1 ( a, b ) SELECT * FROM ( SELECT c, d FROM t2 UNION SELECT e, f FROM t3 ) AS dt ON DUPLICATE KEY UPDATE b = b + c;

```
new Builder()
    ->insert( [ 'a', 'b' ] )
    ->into( 't1' )
    ->values( 
        new Builder()->select( * )
            ->from( 
                new Builder()
                    ->select( [ 'c', 'd' ] )
                    ->from( 't2' )
                    ->union(
                        new Builder()
                            ->select( [ 'e', 'f' ] )
                            ->from( 't3' )
                    ),
            )->as( 'td' )->update( [ '`b` = `b' + `c`' ] );
    )
```

INSERT INTO tbl_temp2 ( fld_id ) SELECT tbl_temp1.fld_order_id FROM tbl_temp1 WHERE tbl_temp1.fld_order_id > 100;

# REPLACE

```
REPLACE [LOW_PRIORITY | DELAYED]
    [INTO] tbl_name
    [PARTITION (partition_name [, partition_name] ...)]
    [(col_name [, col_name] ...)]
    {VALUES | VALUE} (value_list) [, (value_list)] ...

REPLACE [LOW_PRIORITY | DELAYED]
    [INTO] tbl_name
    [PARTITION (partition_name [, partition_name] ...)]
    SET assignment_list

REPLACE [LOW_PRIORITY | DELAYED]
    [INTO] tbl_name
    [PARTITION (partition_name [, partition_name] ...)]
    [(col_name [, col_name] ...)]
    SELECT ...

value:
    {expr | DEFAULT}

value_list:
    value [, value] ...

assignment:
    col_name = value

assignment_list:
    assignment [, assignment] ...
```

REPLACE INFO test VALUES ( 1, 'a', 'b' );


# DELETE

```
DELETE [LOW_PRIORITY] [QUICK] [IGNORE] FROM tbl_name
    [PARTITION (partition_name [, partition_name] ...)]
    [WHERE where_condition]
    [ORDER BY ...]
    [LIMIT row_count]

DELETE [LOW_PRIORITY] [QUICK] [IGNORE]
    tbl_name[.*] [, tbl_name[.*]] ...
    FROM table_references
    [WHERE where_condition]
```

Multiple-table syntax:

```
DELETE [LOW_PRIORITY] [QUICK] [IGNORE]
    FROM tbl_name[.*] [, tbl_name[.*]] ...
    USING table_references
    [WHERE where_condition]
```

DELETE FROM t1 WHERE user = 'jcole' ORDER BY id LIMIT 0, 1;

DELETE t1, t2 FROM t1 INNER JOIN t2 INNER JOIN t3 WHERE t1.id = t2.id AND t2.id = t3.id;

DELETE FROM t1, t2 USING t1 INNER JOIN t2 INNER JOIN t3 WHERE t1.id = t2.id AND t2.id = t3.id;

DELETE t1 FROM t1 LEFT JOIN t2 ON t1.id = t2.id WHERE t2.id IS NULL;

DELETE a1, a2 FROM t1 AS a1 INNER JOIN t2 AS a2 WHERE a1.id = a2.id;

DELETE FROM a1, a2 USING t1 AS a1 INNER JOIN t2 AS a2 WHERE a1.id = a2.id;


# UPDATE

```
UPDATE [LOW_PRIORITY] [IGNORE] table_reference
    SET assignment_list
    [WHERE where_condition]
    [ORDER BY ...]
    [LIMIT row_count]

value:
    {expr | DEFAULT}

assignment:
    col_name = value

assignment_list:
    assignment [, assignment] ...
```

Multiple-table syntax:

```
UPDATE [LOW_PRIORITY] [IGNORE] table_references
    SET assignment_list
    [WHERE where_condition]
```

UPDATE t1 SET col1 = col1 + 1, col2 = col1;

UPDATE t SET id = id + 1 ORDER BY id DESC;

UPDATE t1, t2 SET t1.col1 = t2.col1 WHERE t1.id = t2.id;

# SELECT

```
SELECT
    [ALL | DISTINCT | DISTINCTROW ]
      [HIGH_PRIORITY]
      [STRAIGHT_JOIN]
      [SQL_SMALL_RESULT] [SQL_BIG_RESULT] [SQL_BUFFER_RESULT]
      [SQL_CACHE | SQL_NO_CACHE] [SQL_CALC_FOUND_ROWS]
    select_expr [, select_expr ...]
    [FROM table_references
      [PARTITION partition_list]
    [WHERE where_condition]
    [GROUP BY {col_name | expr | position}
      [ASC | DESC], ... [WITH ROLLUP]]
    [HAVING where_condition]
    [ORDER BY {col_name | expr | position}
      [ASC | DESC], ...]
    [LIMIT {[offset,] row_count | row_count OFFSET offset}]
    [PROCEDURE procedure_name(argument_list)]
    [INTO OUTFILE 'file_name'
        [CHARACTER SET charset_name]
        export_options
      | INTO DUMPFILE 'file_name'
      | INTO var_name [, var_name]]
    [FOR UPDATE | LOCK IN SHARE MODE]]
```

# JOIN

 > `JOIN`, `CROSS JOIN`, and `INNER JOIN` are syntactic equivalents (they can replace each other). In standard SQL, they are not equivalent. `INNER JOIN` is used with an `ON` clause, `CROSS JOIN` is used otherwise.

 > The query optimizer is free to rearrange the join-order of tables in a query to any logically-consistent sequence based on its estimates of the costs of the query, unless you use `STRIGHT_JOIN`, which forces the optimizer to read the left table before the right table in that particular join. In MySQL, you can also `SELECT STRIGHT_JOIN ...` which forces all the tables to be handled in the order specified in the `FROM` clause.

 > A `NATURAL JOIN` is a join (you can have either `NATURAL LEFT` or `NATURAL RIGHT`) that assumes the join criteria to be where same-named columns in both table match.

 > `NATURAL JOIN` is not standard.

SELECT * FROM t1 LEFT JOIN ( t2, t3, t4 ) ON ( t2.a = t1.a AND t2.b = t1.b AND t4.c = t1.c );

SELECT * FROM t1 LEFT JOIN ( t2 CROSS JOIN t3 CROSS JOIN t4 ) ON ( t2.a = t1.a AND t3.b = t1.b AND t4.c = t1.c );

SELECT t1.name t2.salary FROM emloyee AS t1 INNER JOIN info AS t2 ON t1.name = t2.name;

SELECT t1.name t2.salary FROM emloyee t1 INNER JOIN info t2 ON t1.name = t2.name;

SELECT * FROM ( SELECT 1, 2, 3 ) AS t1;

SELECT * FROM ( SELECT 1 AS a, 2 AS b, 3 AS c ) AS t1;

SELECT left_tbl.* FROM left_tbl LEFT JOIN right_tbl ON left_tbl.id = right_tbl.id WHERE right_tbl.id IS NULL;

SELECT * FROM t1, t2;

SELECT * FROM t1 INNER JOIN t2 ON t1.id = t2.id;

SELECT * FROM t1 LEFT JOIN t2 ON t1.id = t2.id;

SELECT * FROM t1 LEFT JOIN t2 USING( id );

SELECT * FROM t1 LEFT JOIN t2 ON t1.id = t2.id LEFT JOIN t3 ON t2.id = t3.id

SELECT 'x' IN ( 'x', 'y', 'z' );

SELECT ( 3, 4 ) IN ( ( 1, 2 ), ( 3, 4 ) );

SELECT * FROM (SELECT year, SUM(profit) FROM sales GROUP BY year WITH ROLLUP) AS dt ORDER BY year;
