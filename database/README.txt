HOTEL MANAGEMENT SYSTEM - FULL OPERATIONS UPDATE

1. Import database/hotel_operations.sql in phpMyAdmin after selecting the hotel_management database.
2. Keep the existing database/config/database.php unchanged.
3. Open Admin > Settings > Import Hotel Data.
4. Upload database/hotel_5_year_demo_data.zip to load the historical demo dataset.
5. The demo dataset is synthetic/test data, not real hotel records.

New modules:
Staff, Attendance, Shifts, Leave, Housekeeping, Maintenance, Expenses,
Inventory, Suppliers, Hotel Services, Service Orders, Laundry,
Restaurant Orders, Payroll, Refunds, Room Transfers, Daily Closing.

The import page supports CSV and ZIP. If PHP ZipArchive is unavailable on Windows/XAMPP,
it attempts PowerShell Expand-Archive as a fallback.

Import notes for the supplied demo ZIP:
- staff.csv employee_code maps to staff.employee_no; CSV email/hire_date are ignored because the current staff table has no such columns.
- maintenance.csv has no request_date, so those rows are skipped and reported instead of inserting fake dates.
- payments.csv method Bank is not valid for the current payments enum, so those rows are skipped and reported.
