package ca.formulize.android.data;

import android.provider.BaseColumns;

public class FormulizeDBContract {
	
	public FormulizeDBContract() {}
	
	public static abstract class ConnectionEntry implements BaseColumns {
		public static final String TABLE_NAME = "ConnectionEntry";
		public static final String COLUMN_NAME_CONNECTION_URL = "ConnectionURL";
		public static final String COLUMN_NAME_CONNECTION_NAME = "ConnectionName";
		public static final String COLUMN_NAME_USERNAME = "Username";
		public static final String COLUMN_NAME_PASSWORD = "Password";
	}
}
