package com.example.formulizeprototype;

import ca.formulize.android.connection.FUserSession;
import android.app.Activity;
import android.content.Intent;
import android.os.Bundle;
import android.view.Menu;
import android.view.View;
import android.view.View.OnClickListener;
import android.widget.Button;
import android.widget.EditText;

/*
 * This Activity is currently not being used by the prototype
 */
public class LoginActivity extends Activity {
	
	public final static String FORMULIZE_URL = "formulizeURL";
	public final static String USERNAME = "username";
	public final static String PASSWORD = "password";

	@Override
	protected void onCreate(Bundle savedInstanceState) {
		super.onCreate(savedInstanceState);
		setContentView(R.layout.activity_login);

		Button signInButton = (Button) findViewById(R.id.signIn);

		signInButton.setOnClickListener(new OnClickListener() {
			public void onClick(View v) {
				EditText formulizeURL = (EditText) findViewById(R.id.fHostNameText);
				EditText username = (EditText) findViewById(R.id.usernameText);
				EditText password = (EditText) findViewById(R.id.passwordText);

				// Open the web view activity with account details passed through
//				Intent formIntent = new Intent(LoginActivity.this,
//						FormActivity.class);
				Intent formIntent = new Intent(LoginActivity.this,
						FormActivity.class);
				formIntent.putExtra(FORMULIZE_URL, formulizeURL.getText().toString());
				formIntent.putExtra(USERNAME, username.getText().toString());
				formIntent.putExtra(PASSWORD, password.getText().toString());
				
				// Create a user session
				FUserSession userSession = FUserSession.getInstance();
				
				// Deprecated methods, use FUserSession's createSession method instead
				//userSession.setFURL(formulizeURL.getText().toString());
				//userSession.setUsername(username.getText().toString());

				startActivity(formIntent);
			}
		});
	}

	@Override
	public boolean onCreateOptionsMenu(Menu menu) {
		// Inflate the menu; this adds items to the action bar if it is present.
		getMenuInflater().inflate(R.menu.login, menu);
		return true;
	}

}
