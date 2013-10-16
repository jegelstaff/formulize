package ca.formulize.android.connection;

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
	private ConnectionInfo connectionInfo;
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

	public ConnectionInfo getConnectionInfo() {
		return connectionInfo;
	}

	public void setConnectionInfo(ConnectionInfo connectionInfo) {
		this.connectionInfo = connectionInfo;
	}

	/**
	 * Starts the process to login to a Formulize server
	 * 
	 * @param activity
	 *            The active application activity to attach the login process
	 *            onto.
	 * @param connectionInfo
	 *            Connection information on which server to connect with the
	 *            necessary credentials.
	 */
	public void createConnection(FragmentActivity activity,
			ConnectionInfo connectionInfo) {

		if (connectionInfo.getUsername() == null
				|| connectionInfo.getUsername().equals("")) {
			askLoginCredentials(activity, connectionInfo, false);

		} else {
			new LoginTask(activity).execute(connectionInfo);
		}
	}

	/**
	 * Prompt for login credentials with {@link UserLoginDialogFragment}
	 * 
	 * @param activity
	 *            The active application activity to attach the
	 *            UserLoginDialogFragment onto.
	 * @param connectionInfo
	 *            Connection information containing server URL and name.
	 * @param isReattempt
	 *            Indicates whether login credentials have been asked before and
	 *            was incorrect.
	 */
	public void askLoginCredentials(FragmentActivity activity,
			ConnectionInfo connectionInfo, boolean isReattempt) {
		UserLoginDialogFragment loginDialog = new UserLoginDialogFragment();
		Bundle args = new Bundle();
		args.putParcelable(UserLoginDialogFragment.EXTRA_CONNECITON_INFO,
				connectionInfo);
		args.putBoolean(UserLoginDialogFragment.EXTRA_IS_REATTEMPT, isReattempt);
		loginDialog.setArguments(args);
		loginDialog.show(activity.getSupportFragmentManager(), "login");
	}

	public boolean isValidConnection(ConnectionInfo connectionInfo) {
		// TODO: Check if connection is a valid Formulize server
		return true;
	}

	public boolean isValidSession() {

		// TODO: Check if current session is still valid
		return true;
	}

	/**
	 * This AsyncTask logs in to a Formulize server with the user credentials
	 * given by the ConnectionInfo it receives. If the login is successful, it
	 * returns the token of the user session as a result and associates the
	 * valid ConnectionInfo and token to FUserSession.
	 * 
	 * @author timch326
	 * 
	 */
	private class LoginTask extends AsyncTask<ConnectionInfo, String, String> {
		
		private ProgressDialog progressDialog;
		private FragmentActivity activity; // Application context
		private ConnectionInfo connectionInfo; // Login Info
		
		public LoginTask(FragmentActivity activity) {
			this.activity = activity;
			progressDialog = new ProgressDialog(activity);
		}

		protected void onPreExecute() {
			this.progressDialog.setMessage("Logging in");
			progressDialog.show();
		}

		@Override
		protected String doInBackground(ConnectionInfo... info) {
			ConnectionInfo connectionInfo = info[0];

			try {
				// Simulate Network Access
				Thread.sleep(2000);

				// Simulate incorrect password
				if (connectionInfo.getPassword().equals("bad")
						|| connectionInfo.getPassword().equals("")) {
					return "BAD_PASSWORD";
				} else {
					// Associate user session with valid connection info
					FUserSession.this.connectionInfo = connectionInfo;
				}

			} catch (InterruptedException e) {
				e.printStackTrace();
			}

			// Login and get token
			return "Logged In";
		}

		protected void onPostExecute(String result) {
			if (progressDialog.isShowing()) {
				progressDialog.dismiss();
			}
			super.onPostExecute(result);
			
			// Bad server Connection
			if (result == null)
				Log.d("Formulize", "Connection Failed");

			// Ask for credentials again if they were incorrect
			else if (result == "BAD_PASSWORD")
				askLoginCredentials(activity, connectionInfo, true);

			else {
				Log.d("Formulize", result);
				userToken = result;

				// Go to application list once logged in
				Intent viewApplicationsIntent = new Intent(activity,
						ApplicationListActivity.class);
				activity.startActivity(viewApplicationsIntent);
			}
		}

	}
}
