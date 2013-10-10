package com.example.formulizeprototype;

import org.apache.http.util.EncodingUtils;

import android.annotation.TargetApi;
import android.app.Activity;
import android.content.Intent;
import android.graphics.Bitmap;
import android.net.Uri;
import android.os.Build;
import android.os.Bundle;
import android.support.v4.app.NavUtils;
import android.util.Log;
import android.view.Menu;
import android.view.MenuItem;
import android.webkit.CookieManager;
import android.webkit.CookieSyncManager;
import android.webkit.WebView;
import android.webkit.WebViewClient;

public class FormActivity extends Activity {

	private WebView webView;
	private String fURL;
	private String username;
	private String password;
	private Boolean isFormPage = false;

	@Override
	protected void onCreate(Bundle savedInstanceState) {

		super.onCreate(savedInstanceState);
		setContentView(R.layout.activity_form);
		// Show the Up button in the action bar.
		setupActionBar();

		// Get Intent Parameters
		Intent intent = getIntent();
		fURL = intent.getStringExtra(LoginActivity.FORMULIZE_URL);
		username = intent.getStringExtra(LoginActivity.USERNAME);
		password = intent.getStringExtra(LoginActivity.PASSWORD);

		String fLoginURL = fURL + "/user.php";
		String postData = "uname=" + username + "&pass=" + password
				+ "&op=login";

		// Clean cookies
		CookieSyncManager.createInstance(this);
		CookieManager cookieManager = CookieManager.getInstance();
		cookieManager.removeAllCookie();

		// Load sample webpage
		webView = (WebView) findViewById(R.id.webview);
		webView.setWebViewClient(new FormulizeWebViewClient());

		Log.d("Formulize", fLoginURL);

		webView.postUrl(fLoginURL, EncodingUtils.getBytes(postData, "base64"));

		Log.d("Formulize", postData);
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
		getMenuInflater().inflate(R.menu.form, menu);
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
		}
		return super.onOptionsItemSelected(item);
	}

	// private void openFormPage() {
	// String fFormURL = fURL + "/modules/formulize/index.php?sid=1";
	// isFormPage = true;
	// webView.loadUrl(fFormURL);
	// }

	// Temporary workaround to prevent the client from being redirected to
	// incorrect URLs (i.e. localhost) by ICMS
	private class FormulizeWebViewClient extends WebViewClient {

		@Override
		public void onPageFinished(WebView view, String url) {
			String cookies = CookieManager.getInstance().getCookie(url);
			
			// TODO: Confirm Login better
			if (!isFormPage && cookies != null
					&& cookies.toUpperCase().contains("ICMS")) {

				// TODO: Get the actual application list, goto Dummy Application List
				Intent screenListIntent = new Intent(FormActivity.this,
						ApplicationListActivity.class);
				startActivity(screenListIntent);
			}
			Log.d("Formulize", "Cookies:" + cookies);
		}

		@Override
		public void onPageStarted(WebView view, String url, Bitmap favicon) {
			Log.d("Formulize", "Loading " + url);
		}

		@Override
		public boolean shouldOverrideUrlLoading(WebView view, String url) {
			String hostname = Uri.parse(url).getHost();
			if (!hostname.contains("localhost")) {
				return false;
			}
			Log.d("Formulize", "Prevented " + hostname + " from loading.");

			// Replace localhost with Android local machine's "localhost" IP
			view.loadUrl(url.replace("localhost", "10.0.2.2"));

			return true;
		}
	}
}
