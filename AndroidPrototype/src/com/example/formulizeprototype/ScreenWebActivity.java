package com.example.formulizeprototype;

import ca.formulize.android.connection.FUserSession;
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
import android.webkit.WebChromeClient;
import android.webkit.WebView;
import android.webkit.WebViewClient;

public class ScreenWebActivity extends Activity {
	public static final String SID = "Screen ID";
	private WebView webView;

	@Override
	protected void onCreate(Bundle savedInstanceState) {
		super.onCreate(savedInstanceState);	
        
        // Show the Up button in the action bar.
		setupActionBar();
		
        webView = new WebView(this);
        setContentView(webView);	
        
        Intent screenIntent = getIntent();
        String sid = screenIntent.getStringExtra(SID);
		
        // Get user session info
        FUserSession userSession = FUserSession.getInstance();
        
		// Load screen page
		webView.setWebViewClient(new FScreenWebViewClient());
		webView.setWebChromeClient(new WebChromeClient());
		webView.getSettings().setJavaScriptEnabled(true);
		String fFormURL = userSession.getConnectionInfo().getConnectionURL() + "/modules/formulize/index.php?sid=" + sid;
		webView.loadUrl(fFormURL);		
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
		getMenuInflater().inflate(R.menu.screen_web, menu);
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
	
	private class FScreenWebViewClient extends WebViewClient {
	    
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
