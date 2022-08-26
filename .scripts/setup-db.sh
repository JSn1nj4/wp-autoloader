#!/usr/bin/env bash
# anticipating setting up DB-related tasks at some point
SQL_BASE="/app/.config/mysql.sql.gz"
SQL_BACKUP="/app/.config/mysql-backup.sql.gz"

if [[ -f "$SQL_BACKUP" ]]; then
  echo "Importing DB backup..."
  /helpers/sql-import.sh $SQL_BACKUP
  echo "DB backup import complete!"
  exit 0
fi

if [[ -f "$SQL_BASE" ]]; then
  echo "No DB backup found. Importing starter DB..."
  /helpers/sql-import.sh $SQL_BASE
  echo "Base DB import complete!"
  exit 0
fi

echo "No SQL file found to import. Skipping."
