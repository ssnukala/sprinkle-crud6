# Table Format Summary for Schema Test Results

**Added in commit:** 1a500bd  
**Requested by:** @ssnukala in comment #3640205801

## Overview

The test scripts now output a comprehensive table summary showing all test results in a structured table format with the following columns:

- **Schema** - The schema/model being tested
- **Activity** - The action/operation being performed
- **Pass/Fail** - Test result
- **Status** - HTTP status code
- **Message** - Success message or error description

## Table Output Example

```
=========================================
Test Results by Schema and Activity (Table Format)
=========================================

| Schema     | Activity     | Pass/Fail | Status   | Message                                            |
|------------|--------------|-----------|----------|----------------------------------------------------|
| activities | create       | FAIL      | 500      | Foreign key constraint fails                       |
| groups     | create       | PASS      | 200      | Success                                            |
| groups     | delete       | PASS      | 200      | Success                                            |
| groups     | list         | PASS      | 200      | Success                                            |
| groups     | read         | PASS      | 200      | Success                                            |
| groups     | schema       | PASS      | 200      | Success                                            |
| groups     | update       | PASS      | 200      | Success                                            |
| roles      | list         | PASS      | 200      | Success                                            |
| roles      | read         | PASS      | 200      | Success                                            |
| users      | create       | FAIL      | 500      | SQLSTATE[23000]: Integrity constraint violation... |
| users      | delete       | FAIL      | 403      | Permission denied                                  |
| users      | list         | PASS      | 200      | Success                                            |
| users      | read         | PASS      | 200      | Success                                            |
| users      | schema       | PASS      | 200      | Success                                            |
| users      | update       | PASS      | 200      | Success                                            |
```

## Features

### Automatic Sorting
- Results are sorted alphabetically by schema name
- Within each schema, activities are sorted alphabetically
- Makes it easy to scan and find specific schema/activity combinations

### Dynamic Column Widths
- Column widths automatically adjust based on content length
- Ensures proper alignment regardless of schema/activity name length
- Minimum widths prevent table from collapsing

### Message Truncation
- Long error messages are truncated to 50 characters
- Prevents table from becoming too wide to read
- Adds "..." suffix when message is truncated
- Full messages still available in detailed reports below the table

### Comprehensive Coverage
- Shows ALL test results in one view
- Combines both passed and failed tests
- Easy to see at a glance which schemas are fully functional

## Table Columns Detail

### Schema Column
- **Content:** Name of the schema/model being tested
- **Examples:** users, groups, roles, permissions, activities
- **Width:** Dynamic (minimum 10 characters)

### Activity Column
- **Content:** Action/operation being performed on the schema
- **Examples:** list, read, create, update, delete, schema, update_field
- **Width:** Dynamic (minimum 12 characters)

### Pass/Fail Column
- **Content:** Test result
- **Values:** 
  - `PASS` - Test succeeded
  - `FAIL` - Test failed
- **Width:** Fixed at 9 characters

### Status Column
- **Content:** HTTP status code from the API response
- **Examples:**
  - `200` - Success
  - `403` - Permission denied (Forbidden)
  - `500` - Server error
  - `N/A` - Status not available (exception cases)
- **Width:** Fixed at 8 characters

### Message Column
- **Content:** Description of the result
- **Pass Messages:** "Success"
- **Fail Messages:** Error description from server
- **Examples:**
  - "Success"
  - "Permission denied"
  - "SQLSTATE[23000]: Integrity constraint violation..."
  - "Foreign key constraint fails"
- **Width:** Fixed at 50 characters (truncated with "..." if longer)

## Location in Output

The table appears **after** the test summary statistics and **before** the detailed failure/success reports:

```
=========================================
Test Summary
=========================================
Total tests: 45
Passed: 38
Warnings: 5
Failed: 2
Skipped: 0

=========================================
Test Results by Schema and Activity (Table Format)
=========================================

| Schema | Activity | Pass/Fail | Status | Message |
|--------|----------|-----------|--------|---------|
| ...    | ...      | ...       | ...    | ...     |

=========================================
Failure Report by Schema
=========================================
(detailed failure information)

=========================================
Success Report by Schema
=========================================
(detailed success information)
```

## Use Cases

### Quick Status Check
Scan the table to see overall health of all schemas:
- How many tests passed vs failed per schema?
- Which activities are working vs failing?
- What are the status codes (permission vs server errors)?

### Comparing Schemas
Easily compare different schemas:
- Which schemas are fully functional (all PASS)?
- Which schemas have the most failures?
- Are failures consistent across schemas (pattern detection)?

### Identifying Issues
Quickly spot problematic areas:
- All creates failing? → Check payload generation
- All deletes return 403? → Check permissions
- Random 500 errors? → Check server logs
- Specific schema failing? → Check schema definition

### Reporting
Use the table for status reports:
- Copy/paste into issue reports
- Include in documentation
- Share with team for discussion
- Track progress over time

## Implementation Details

### Code Location
- `test-authenticated-api-paths.js` - Line ~483-565
- `take-screenshots-with-tracking.js` - Line ~1203-1285

### Data Sources
- `successBySchema` - Object tracking successful tests
- `failuresBySchema` - Object tracking failed tests

### Algorithm
1. Collect all unique schemas from both success and failure objects
2. Sort schemas alphabetically
3. For each schema:
   - Extract successful actions (sorted alphabetically)
   - Extract failed actions (sorted alphabetically)
   - Create table row for each action
4. Calculate maximum column widths based on content
5. Print formatted table with proper alignment

## Benefits

1. **At-a-Glance Overview**: See all results in one structured view
2. **Easy Scanning**: Table format makes it easy to scan for patterns
3. **Sortable Data**: Alphabetical sorting makes finding specific items easy
4. **Consistent Format**: Always displays in the same format for consistency
5. **Copy-Paste Friendly**: Can be copied and shared easily
6. **Markdown Compatible**: Table format works well in markdown documents

## Future Enhancements

Potential improvements for the table format:

1. **Color Coding**: Add ANSI colors (green for PASS, red for FAIL)
2. **CSV Export**: Option to export table as CSV file
3. **Filtering**: Command-line options to filter by schema or status
4. **Grouping**: Option to group by schema with subtotals
5. **Summary Row**: Add totals row at bottom
6. **HTML Export**: Generate HTML version with sortable columns

## Related Documentation

- Main implementation: `.archive/API_TEST_FAILURE_HANDLING_IMPLEMENTATION.md`
- Quick reference: `.archive/API_TEST_REPORT_QUICK_REFERENCE.md`
- Before/after: `.archive/API_TEST_BEFORE_AFTER_COMPARISON.md`
- Complete summary: `.archive/API_TEST_FAILURE_HANDLING_COMPLETE_SUMMARY.md`
