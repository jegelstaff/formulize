package ca.formulize.android.data;

import android.content.ContentValues;
import android.content.Context;
import android.database.Cursor;
import android.database.sqlite.SQLiteDatabase;
import android.database.sqlite.SQLiteOpenHelper;
import android.util.Log;
import ca.formulize.android.data.FormulizeDBContract.ConnectionEntry;

/**
 * A helper class that handles with operations involving Android's SQLite
 * Database.
 * 
 * @author timch326
 * 
 */
public class FormulizeDBHelper extends SQLiteOpenHelper {

	public static final int DATABASE_VERSION = 3;
	public static final String DATABASE_NAME = "Formulize.db";

	private static final String SQL_CREATE_ENTRIES = "CREATE TABLE "
			+ ConnectionEntry.TABLE_NAME + " (" + ConnectionEntry._ID
			+ " INTEGER PRIMARY KEY,"
			+ ConnectionEntry.COLUMN_NAME_CONNECTION_NAME + " TEXT,"
			+ ConnectionEntry.COLUMN_NAME_CONNECTION_URL + " TEXT,"
			+ ConnectionEntry.COLUMN_NAME_USERNAME + " TEXT,"
			+ ConnectionEntry.COLUMN_NAME_PASSWORD + " TEXT " + " )";

	private static final String SQL_DELETE_ENTRIES = "DROP TABLE IF EXISTS "
			+ ConnectionEntry.TABLE_NAME;

	public FormulizeDBHelper(Context context) {
		super(context, DATABASE_NAME, null, DATABASE_VERSION);
	}

	@Override
	public void onCreate(SQLiteDatabase db) {
		Log.d("Formulize", SQL_CREATE_ENTRIES);
		db.execSQL(SQL_CREATE_ENTRIES);
	}

	@Override
	public void onUpgrade(SQLiteDatabase db, int oldVersion, int newVersion) {
		db.execSQL(SQL_DELETE_ENTRIES);
		onCreate(db);
	}

	/**
	 * This inserts ConnectionInfo objects into the SQLite database
	 * 
	 * @param connectionInfo
	 * @return the unique ID of the connection info inserted
	 */
	public long insertConnectionInfo(ConnectionInfo connectionInfo) {
		SQLiteDatabase db = this.getWritableDatabase();

		Log.d("Formulize", "Inserting into Database!");

		ContentValues values = new ContentValues();
		values.put(ConnectionEntry.COLUMN_NAME_CONNECTION_NAME,
				connectionInfo.getConnectionName());
		values.put(ConnectionEntry.COLUMN_NAME_CONNECTION_URL,
				connectionInfo.getConnectionURL());
		values.put(ConnectionEntry.COLUMN_NAME_USERNAME,
				connectionInfo.getUsername());
		values.put(ConnectionEntry.COLUMN_NAME_PASSWORD,
				connectionInfo.getPassword());

		return db.insert(ConnectionEntry.TABLE_NAME, null, values);
	}

	/**
	 * Returns a cursor that selects all the connection info entries saved in
	 * the database
	 * 
	 * @return a cursor containing all saved connection info
	 */
	public Cursor getConnectionList() {
		String[] projection = { ConnectionEntry._ID,
				ConnectionEntry.COLUMN_NAME_CONNECTION_URL,
				ConnectionEntry.COLUMN_NAME_CONNECTION_NAME,
				ConnectionEntry.COLUMN_NAME_USERNAME,
				ConnectionEntry.COLUMN_NAME_PASSWORD };

		String sortOrder = ConnectionEntry._ID;

		SQLiteDatabase db = this.getReadableDatabase();
		return db.query(ConnectionEntry.TABLE_NAME, projection, null, null,
				null, null, sortOrder);
	}

}
