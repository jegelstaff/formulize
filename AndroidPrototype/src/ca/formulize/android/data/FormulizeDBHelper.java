package ca.formulize.android.data;

import android.content.ContentValues;
import android.content.Context;
import android.database.Cursor;
import android.database.sqlite.SQLiteDatabase;
import android.database.sqlite.SQLiteOpenHelper;
import android.util.Log;
import ca.formulize.android.data.FormulizeDBContract.ConnectionEntry;

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

	public long insertConnectionInfo(ConnectionInfo connInfo) {
		SQLiteDatabase db = this.getWritableDatabase();
		
		Log.d("Formulize", "Inserting into Database!");

		ContentValues values = new ContentValues();
		values.put(ConnectionEntry.COLUMN_NAME_CONNECTION_NAME,
				connInfo.getConnectionName());
		values.put(ConnectionEntry.COLUMN_NAME_CONNECTION_URL,
				connInfo.getConnectionURL());
		values.put(ConnectionEntry.COLUMN_NAME_USERNAME, connInfo.getUsername());
		values.put(ConnectionEntry.COLUMN_NAME_PASSWORD, connInfo.getPassword());

		return db.insert(ConnectionEntry.TABLE_NAME, null, values);
	}

	public Cursor getConnectionList() {
		String[] projection = { ConnectionEntry._ID,
				ConnectionEntry.COLUMN_NAME_CONNECTION_URL,
				ConnectionEntry.COLUMN_NAME_CONNECTION_NAME,
				ConnectionEntry.COLUMN_NAME_USERNAME,
				ConnectionEntry.COLUMN_NAME_PASSWORD };
		
		String sortOrder = ConnectionEntry._ID;
		
		SQLiteDatabase db = this.getReadableDatabase();
		return db.query(ConnectionEntry.TABLE_NAME, projection, null, null, null, null, sortOrder);
	}

	public ConnectionInfo getConnectionInfo(long id) {
		// TODO Auto-generated method stub
		return new ConnectionInfo("http://192.168.1.119:8888", "Localhost", "", "");
	}

}
