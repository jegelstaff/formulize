package ca.formulize.android.connection;

import android.app.Activity;
import android.app.ProgressDialog;
import android.content.Intent;
import android.os.AsyncTask;
import android.os.Bundle;
import android.support.v4.app.FragmentActivity;
import android.util.Log;
import ca.formulize.android.data.ConnectionInfo;

import com.example.formulizeprototype.ApplicationListActivity;

public class FUserSession {
	public final static String LOGIN_FAILED = "Login has failed";
	public final static String CONNECTION_FAILED = "Invalid Formulize Connection";
	public final static String NO_USER_CREDENTIALS = "Please enter a username and password";

	private static FUserSession instance;
	private String username;
	private String fURL;
	private String password;
	private String userToken;

	public static FUserSession getInstance() {
		if (instance == null) {
			instance = new FUserSession();
			return instance;
		} else
			return instance;
	}

	private FUserSession() {
	}

	public String getUsername() {
		return username;
	}

	public String getFURL() {
		return fURL;
	}

	public void setUsername(String newUsername) {
		username = newUsername;
	}

	public void setFURL(String newFURL) {
		fURL = newFURL;
	}

	public void createConnection(FragmentActivity activity,
			ConnectionInfo connectionInfo) {

		if (connectionInfo.getUsername() == null
				|| connectionInfo.getUsername() == "") {
			
			// Prompt for login credentials with dialog
			// Pass existing connection info to the login dialog
			UserLoginDialogFragment loginDialog = new UserLoginDialogFragment();
			Bundle args = new Bundle();
			args.putParcelable(UserLoginDialogFragment.EXTRA_CONNECITON_INFO, connectionInfo);
			loginDialog.setArguments(args);
			loginDialog.show(activity.getSupportFragmentManager(), "login");
		} else {
			new LoginTask(activity).execute(connectionInfo);
		}
	}

	public boolean isValidConnection(ConnectionInfo connectionInfo) {

		// Check if connection is a valid Formulize server
		return true;
	}

	public boolean isValidSession() {

		// TODO: Check if current session is still valid
		return true;
	}

	private class LoginTask extends AsyncTask<ConnectionInfo, String, String> {

		public LoginTask(Activity activity) {
			this.activity = activity;
			dialog = new ProgressDialog(activity);
		}

		// Progress Dialog to show user that login is in progress
		private ProgressDialog dialog;
		// Application context
		private Activity activity;

		// Login info

		protected void onPreExecute() {
			this.dialog.setMessage("Logging in");
			dialog.show();
		}

		@Override
		protected String doInBackground(ConnectionInfo... info) {
			ConnectionInfo connectionInfo = info[0];
			if (connectionInfo.getUsername() == null) {
				return NO_USER_CREDENTIALS;
			}
			if (connectionInfo.getConnectionURL() == null) {
				return CONNECTION_FAILED;
			}

			try {
				// Simulate Network Access
				Thread.sleep(2000);
			} catch (InterruptedException e) {
				e.printStackTrace();
			}

			// Login and get token
			return "Logged In";
		}

		protected void onPostExecute(String result) {
			if (dialog.isShowing()) {
				dialog.dismiss();
			}

			super.onPostExecute(result);
			if (result == null) {
				Log.d("Formulize", "Connection Failed");
			}
			Log.d("Formulize", result);
			Intent viewApplicationsIntent = new Intent(activity,
					ApplicationListActivity.class);
			activity.startActivity(viewApplicationsIntent);
		}

	}
}
