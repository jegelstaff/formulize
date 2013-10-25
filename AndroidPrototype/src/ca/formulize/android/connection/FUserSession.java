package ca.formulize.android.connection;

import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.net.CookieHandler;
import java.net.CookieManager;
import java.net.CookiePolicy;
import java.net.HttpURLConnection;
import java.net.MalformedURLException;
import java.net.URL;
import java.net.URLEncoder;
import java.util.List;

import android.app.ProgressDialog;
import android.content.Intent;
import android.os.AsyncTask;
import android.os.Bundle;
import android.support.v4.app.FragmentActivity;
import android.util.Log;
import ca.formulize.android.data.ConnectionInfo;
import ca.formulize.android.menu.ApplicationListActivity;

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
		return this.connectionInfo;
	}

	public void setConnectionInfo(ConnectionInfo connectionInfo) {
		this.connectionInfo = connectionInfo;
	}

	public String getUserToken() {
		return userToken;
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
	 * Login is consider successful when Formulize's user.php returns 2+ cookies
	 * after the credentials have been submitted through POST.
	 * 
	 * TODO: Stop the asynchronous task when the connection is cancelled (back
	 * button is pressed)
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

			// Http Connection Variables
			HttpURLConnection urlConnection = null;
			String response = null;
			int responseCode = 0;

			connectionInfo = info[0];

			try {

				// Set up cookie manager
				CookieHandler.setDefault(new CookieManager(null,
						CookiePolicy.ACCEPT_ALL));

				// Create connection to server and set request parameters
				urlConnection = (HttpURLConnection) new URL(
						connectionInfo.getConnectionURL() + "user.php")
						.openConnection();
				urlConnection.setReadTimeout(10000);
				urlConnection.setConnectTimeout(15000);
				urlConnection.setDoOutput(true); // Triggers POST
				urlConnection.setInstanceFollowRedirects(false);

				// Enter Post Parameters
				String query = String
						.format("op=%s&pass=%s&uname=%s", URLEncoder.encode(
								"login", "UTF-8"), URLEncoder.encode(
								connectionInfo.getPassword(), "UTF-8"),
								URLEncoder.encode(connectionInfo.getUsername(),
										"UTF-8"));

				Log.d("Formulize", query);

				OutputStream output = urlConnection.getOutputStream();
				output.write(query.getBytes("UTF-8"));

				Log.d("Formulize", connectionInfo.getConnectionURL());

				// Check Http Status Code
				urlConnection.connect();
				responseCode = urlConnection.getResponseCode();
				Log.d("Formulize", "Http Status Code: " + responseCode);

				/*
				 * InputStreamReader in = new InputStreamReader(
				 * urlConnection.getInputStream());
				 * 
				 * String inString = readInputToString(in); Log.d("Formulize",
				 * inString);
				 */

				// Check For Cookies
				List<String> cookies = urlConnection.getHeaderFields().get(
						"Set-Cookie");
				Log.d("Formulize", cookies.toString());

				// If there are 2 or more cookeis received, login was successful
				if (cookies.size() >= 2) {
					response = "Logged In";
				} else {
					response = "BAD_PASSWORD";
				}

			} catch (MalformedURLException e) {
				// TODO Auto-generated catch block
				e.printStackTrace();
			} catch (IOException e) {

				// Print Error Response if response code is not 200
				if (responseCode != 200 && responseCode != 0) {
					InputStreamReader in = new InputStreamReader(
							urlConnection.getErrorStream());
					readInputToString(in);

				}

				e.printStackTrace();
				return null;
			}

			return response;
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

				FUserSession.getInstance().connectionInfo = connectionInfo;

				// Go to application list once logged in
				Intent viewApplicationsIntent = new Intent(activity,
						ApplicationListActivity.class);
				activity.startActivity(viewApplicationsIntent);
			}
		}

		/**
		 * Helper function to convert an entire input stream into a String
		 * 
		 * @param in
		 * @return String representation of the input stream
		 */
		private String readInputToString(InputStreamReader in) {
			BufferedReader reader = new BufferedReader(in);
			StringBuilder stringBuilder = new StringBuilder();

			try {
				// Read server response
				String line = null;
				while ((line = reader.readLine()) != null) {
					stringBuilder.append(line + "\n");
				}
				reader.close();
			} catch (IOException e) {
				// TODO Auto-generated catch block
				e.printStackTrace();
			}
			return stringBuilder.toString();

		}

	}
}
