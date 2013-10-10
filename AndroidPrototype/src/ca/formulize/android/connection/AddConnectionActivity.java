package ca.formulize.android.connection;

import android.annotation.TargetApi;
import android.content.Intent;
import android.os.Build;
import android.os.Bundle;
import android.support.v4.app.FragmentActivity;
import android.support.v4.app.NavUtils;
import android.view.Menu;
import android.view.MenuItem;
import android.widget.EditText;
import android.widget.Toast;
import ca.formulize.android.data.ConnectionInfo;
import ca.formulize.android.data.FormulizeDBHelper;

import com.example.formulizeprototype.R;

/**
 * Represents the screen that allows users to create new connections to a
 * Formulize server. Connections are saved once they are submitted and
 * validated.
 * 
 * @author timch326
 * 
 */
public class AddConnectionActivity extends FragmentActivity {

	// Values to populate form with for editing connections
	public final static String EXTRA_CONNECTION_URL = "ca.formulize.android.extra.connectionURL";
	public final static String EXTRA_CONNECTION_NAME = "ca.formulize.android.extra.connectionName";
	public final static String EXTRA_USERNAME = "ca.formulize.android.extra.username";
	public final static String EXTRA_PASSWORD = "ca.formulize.android.extra.password";

	// Values for connection information
	private String connectionURL;
	private String connectionName;
	private String username;
	private String password;

	// UI References
	private EditText connectionURLView;
	private EditText connectionNameView;
	private EditText usernameView;
	private EditText passwordView;

	@Override
	protected void onCreate(Bundle savedInstanceState) {
		super.onCreate(savedInstanceState);
		setContentView(R.layout.activity_add_connection);
		// Show the Up button in the action bar.
		setupActionBar();

		// Set up connection form
		// TODO: Set up connection values if they exist to allow edits
		connectionURLView = (EditText) findViewById(R.id.connection_url);
		connectionNameView = (EditText) findViewById(R.id.connection_name);
		usernameView = (EditText) findViewById(R.id.username);
		passwordView = (EditText) findViewById(R.id.password);
	}

	/**
	 * Set up the {@link android.app.ActionBar}, if the API is available.
	 */
	@TargetApi(Build.VERSION_CODES.HONEYCOMB)
	private void setupActionBar() {
		if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.HONEYCOMB) {
			getActionBar().setDisplayHomeAsUpEnabled(true);
		}
	}

	@Override
	public boolean onCreateOptionsMenu(Menu menu) {
		// Inflate the menu; this adds items to the action bar if it is present.
		getMenuInflater().inflate(R.menu.add_connection, menu);
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
			NavUtils.navigateUpFromSameTask(this);
			return true;
		case R.id.saveConnection:
			connectionURL = connectionURLView.getText().toString();
			connectionName = connectionNameView.getText().toString();
			username = usernameView.getText().toString();
			password = passwordView.getText().toString();

			// TODO: Validate Connection Form

			// TODO: Validate if connection is valid address to
			// TODO: If entered, validate connection login

			// TODO: If Valid, add connection and login to database

			ConnectionInfo connectionInfo = new ConnectionInfo(connectionURL,
					connectionName, username, password);

			if (isValidConnection(connectionInfo)) {
				addConnection(connectionInfo);

				// Return to the connection list
				Toast connectionToast = Toast.makeText(this,
						"Connection Added", Toast.LENGTH_SHORT);
				connectionToast.show();

				Intent connectionListIntent = new Intent(
						AddConnectionActivity.this, ConnectionActivity.class);
				startActivity(connectionListIntent);

			} else {
				connectionURLView.setError("Invalid Connection URL");
			}

		}

		return super.onOptionsItemSelected(item);
	}

	boolean isValidConnection(ConnectionInfo connection) {
		// TODO: Implement the actual validation
		return true;
	}

	private void addConnection(ConnectionInfo connection) {
		FormulizeDBHelper dbHelper = new FormulizeDBHelper(this);
		dbHelper.insertConnectionInfo(connection);
	}
}
