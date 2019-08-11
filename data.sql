DROP TABLE IF EXISTS edev_brpostcode_ranges;

CREATE TABLE edev_brpostcode_ranges (
  ibge_code bigint(7) UNSIGNED NOT NULL,
  range_in bigint(8) UNSIGNED NOT NULL DEFAULT '0',
  range_out bigint(8) UNSIGNED NOT NULL DEFAULT '0',
  city varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  state varchar(2) COLLATE utf8mb4_unicode_520_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

ALTER TABLE edev_brpostcode_ranges
  ADD PRIMARY KEY (ibge_code);

ALTER TABLE edev_brpostcode_ranges
  MODIFY ibge_code bigint(5) UNSIGNED NOT NULL AUTO_INCREMENT;

SELECT city, state  FROM edev_brpostcode_ranges WHERE range_in <= 89220333 AND range_out >= 89220333;
