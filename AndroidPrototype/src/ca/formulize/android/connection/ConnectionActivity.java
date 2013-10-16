package ca.formulize.android.connection;

import android.content.Intent;
import android.database.Cursor;
import android.os.Bundle;
import android.support.v4.app.FragmentActivity;
import android.support.v4.widget.CursorAdapter;
import android.support.v4.widget.SimpleCursorAdapter;
import android.util.Log;
import android.view.ActionMode;
import android.view.Menu;
import android.view.MenuInflater;
import android.view.MenuItem;
import android.view.View;
import android.widget.AdapterView;
import android.widget.AdapterView.OnItemClickListener;
import android.widget.ListView;
import ca.formulize.android.data.ConnectionInfo;
import ca.formulize.android.data.FormulizeDBContract.ConnectionEntry;
import ca.formulize.android.data.FormulizeDBHelper;

import com.example.formulizeprototype.R;

/**
 * Represents the connection list screen where users can choose from a list of
 * connections they have created in {@link AddConnectionActivity} to connect to it.
 * 
 * @author timch326
 * 
 */
public class ConnectionActivity extends FragmentActivity {

	private ListView connectionList;
	private FormulizeDBHelper dbHelper;
	private SimpleCursorAdapter connectionAdapter;
	private ActionMode.Callback actionModeCallback;

	@Override
	protected void onCreate(Bundle savedInstanceState) {
		super.onCreate(savedInstanceState);

		connectionList = new ListView(this);
		setContentView(connectionList);
		
		// Instantiate Connection List
		dbHelper = new FormulizeDBHelper(this);
		Cursor connectionCursor = dbHelper.getConnectionList();
		String[] selectDBColumns = { ConnectionEntry.COLUMN_NAME_CONNECTION_NAME };
		int[] mappedViews = { android.R.id.text1 };
		connectionAdapter = new SimpleCursorAdapter(this,
				android.R.layout.simple_list_item_1, connectionCursor,
				selectDBColumns, mappedViews,
				CursorAdapter.FLAG_REGISTER_CONTENT_OBSERVER);

		connectionList.setAdapter(connectionAdapter);

		// Set up list item click listeners
		OnItemClickListener mConnectionClickedListener = new OnConnectionClickListener();
		connectionList.setOnItemClickListener(mConnectionClickedListener);
	}

	@Override
	public boolean onCreateOptionsMenu(Menu menu) {
		// Inflate the menu; this adds items to the action bar if it is present.
		getMenuInflater().inflate(R.menu.connection, menu);
		return true;
	}

	@Override
	public boolean onOptionsItemSelected(MenuItem item) {
		switch (item.getItemId()) {
		case android.R.id.home:
			// This ID represents the Home or Up button. In the case of this
			// activity, the Up button is shown. Use NavUtils to allow users
			// to navigate up one level in the application structure. For
			// more details, see the Navigation pattern on Android Design:
			//
			// http://developer.android.com/design/patterns/navigation.html#up-vs-back
			//
			return true;
		case R.id.addConnection:
			Intent addConnectionIntent = new Intent(ConnectionActivity.this,
					AddConnectionActivity.class);
			startActivity(addConnectionIntent);
		}
		return super.onOptionsItemSelected(item);
	}

	private class OnConnectionClickListener implements OnItemClickListener {
		public void onItemClick(AdapterView<?> parent, View v, int position,
				long id) {

			// Get selected connection info from database
			Cursor cursor = (Cursor) connectionAdapter.getItem(position);
			String connectionURL = cursor
					.getString(cursor
							.getColumnIndex(ConnectionEntry.COLUMN_NAME_CONNECTION_URL));
			String connectionName = cursor
					.getString(cursor
							.getColumnIndex(ConnectionEntry.COLUMN_NAME_CONNECTION_NAME));
			String username = cursor.getString(cursor
					.getColumnIndex(ConnectionEntry.COLUMN_NAME_USERNAME));
			String password = cursor.getString(cursor
					.getColumnIndex(ConnectionEntry.COLUMN_NAME_PASSWORD));

			ConnectionInfo connectionInfo = new ConnectionInfo(connectionURL,
					connectionName, username, password);
			Log.d("Formulize", "Connection Selected");
			FUserSession session = FUserSession.getInstance();

			// Start Async Login, go to Application List if successful
			session.createConnection(ConnectionActivity.this, connectionInfo);

		}
	}

}
