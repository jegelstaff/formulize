package com.example.formulizeprototype;

import java.util.ArrayList;

import android.annotation.TargetApi;
import android.app.Activity;
import android.content.Intent;
import android.os.Build;
import android.os.Bundle;
import android.support.v4.app.NavUtils;
import android.view.Menu;
import android.view.MenuItem;
import android.view.View;
import android.widget.AdapterView;
import android.widget.AdapterView.OnItemClickListener;
import android.widget.ArrayAdapter;
import android.widget.ListView;
import android.widget.TextView;

public class ScreenListActivity extends Activity {

	public static final String ADMIN_USERNAME = "admin";
	public static final String SCREEN = "Screen";

	@Override
	protected void onCreate(Bundle savedInstanceState) {
		super.onCreate(savedInstanceState);
		setContentView(R.layout.activity_screen_list);
		// Show the Up button in the action bar.
		setupActionBar();
		
		// Get selected application
		Intent screenListIntent = getIntent();
		String application = screenListIntent.getStringExtra(ApplicationListActivity.APPLICATION);
		setTitle(application);
		
		// Initialize list of availible screens for the application
        ArrayList<String> screenList = getApplicationScreens(application, ADMIN_USERNAME);
        ArrayAdapter<String> arrayAdapter =      
        new ArrayAdapter<String>(this,android.R.layout.simple_list_item_1, screenList);
        
		ListView applicationListView = (ListView) findViewById(R.id.screenList);
        applicationListView.setAdapter(arrayAdapter); 
        applicationListView.setOnItemClickListener(new ScreenListClickListener());
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
		getMenuInflater().inflate(R.menu.form_list, menu);
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
	
	// Hardcoded for prototyping
	private ArrayList<String> getApplicationScreens(String application, String user) {
		ArrayList<String> screenList = new ArrayList<String>();
		screenList.add("Animal List");
		screenList.add("Wildlife Activity Form");
		screenList.add("Animal Spotting");
		return screenList;
	}
	
	private class ScreenListClickListener implements OnItemClickListener {

		@Override
		public void onItemClick(AdapterView<?> adapter, View view, int position,
				long screenID) {
			// TODO Auto-generated method stub
			// Go to Application Screen List Activity with selected application
			TextView screenName = (TextView) view.findViewById(android.R.id.text1);

			if (position == 0) {
				Intent screenList = new Intent(ScreenListActivity.this,
						ScreenWebActivity.class);
				screenList.putExtra(ScreenWebActivity.SID, "8");
				startActivity(screenList);
				return;
			}
			Intent screenListIntent = new Intent(ScreenListActivity.this,
					ScreenActivity.class);
			screenListIntent.putExtra(SCREEN, screenName.getText().toString());
			startActivity(screenListIntent);
		}
	}

}
