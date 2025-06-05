# Changelog

## 1.0.2 - 2025-06-05

- Modernized codebase and ensure compatibility between phpBB 3.2, 3.3 and 4.0.

## 1.0.1 - 2020-12-28

- Fix SQL failures with MSSQL databases.
- Align images to the top of the preview tooltip.
- Only load CSS/JS files on pages where they are needed.
- Move all HTML code out of PHP and into the template files.

## 1.0.0 - 2020-08-17

- "F" it, ship it!

### 1.0.0-RC3 - 2020-06-10

- Refactored code into a helper class

### 1.0.0-RC2 - 2020-03-17

- Added forum-based permissions to control which groups and forums can see image previews.
- Added options to enable/disable image previews in search results and Precise Similar Topics.

### 1.0.0-RC1 - 2019-11-22

- Renamed the entire extension project using all lowercase letters instead of snake-case because it's the rules with Composer package names and Packagist.

### 1.0.0-b5 - 2019-06-19

- Added a UCP display preference so users can disable image previews.
- Reverted from Symfony's crawler to PHP's DOMDocument crawler.

### 1.0.0-b4 - 2019-05-22

- Switch to Symfony's crawler to parse images out of posts.

### 1.0.0-b3 - 2019-04-18

- Minor coding fixes.

### 1.0.0-b2 - 2018-06-30

- Improved how custom installation error messages get handled.

### 1.0.0-b1 - 2017-04-21

- Only show images from approved/visible posts.
- Clean up error messages displayed when installed via CLI.

### 1.0.0-a5 - 2017-02-17

- Massively improves database performance, up to 18x faster.

### 1.0.0-a4 - 2017-02-15

- Improve database performance, up to 3x faster.
- Reinstate support for PostgreSQL.

### 1.0.0-a3 - 2017-02-15

- Fix performance and time-out issues on large databases.
- Remove support for PostgreSQL.

### 1.0.0-a2 - 2017-02-14

- Avoid possible conflicts between posts with exact same post time.
- Add support for Precise Similar Topics extension.

### 1.0.0-a1 - 2017-02-11

- First alpha release version.
