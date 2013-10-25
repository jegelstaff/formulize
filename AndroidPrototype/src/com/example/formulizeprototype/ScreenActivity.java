package com.example.formulizeprototype;

import java.util.ArrayList;

import ca.formulize.android.menu.ScreenListActivity;

import android.annotation.TargetApi;
import android.app.Activity;
import android.content.Context;
import android.content.Intent;
import android.os.Build;
import android.os.Bundle;
import android.support.v4.app.NavUtils;
import android.view.LayoutInflater;
import android.view.Menu;
import android.view.MenuItem;
import android.view.View;
import android.view.ViewGroup;
import android.widget.BaseAdapter;
import android.widget.ListView;
import android.widget.TextView;

public class ScreenActivity extends Activity {
	
	private String screenName;

	@Override
	protected void onCreate(Bundle savedInstanceState) {
		super.onCreate(savedInstanceState);
		setContentView(R.layout.activity_screen);
		// Show the Up button in the action bar.
		setupActionBar();
		
		// Set screen title
		Intent screenIntent = getIntent();
		screenName = screenIntent.getStringExtra(ScreenListActivity.SCREEN);
		TextView screenNameView = (TextView) findViewById(R.id.applicationName);
		screenNameView.setText(screenName);
		
		// Initialize form elements
        ScreenElementsAdapter screenElementsAdapter = new ScreenElementsAdapter();
        screenElementsAdapter.addItem("0");
        screenElementsAdapter.addItem("0");
        screenElementsAdapter.addItem("1");
        screenElementsAdapter.addItem("0");
        
		ListView screenElementsList = (ListView) findViewById(R.id.screenElementsList);
		screenElementsList.setAdapter(screenElementsAdapter);
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
		getMenuInflater().inflate(R.menu.screen, menu);
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
	
	// Used template from http://android.amberfog.com/?p=296
	
    private class ScreenElementsAdapter extends BaseAdapter {
    	 
        private static final int ELEMENT_TEXT = 0;
        private static final int ELEMENT_UPLOAD = 1;
        private static final int TYPE_MAX_COUNT = ELEMENT_UPLOAD + 1;	// Think of better way to count form elements
 
        private ArrayList<String> screenElementsData = new ArrayList<String>();
        private LayoutInflater screenElementInflater;
  
        public ScreenElementsAdapter() {
            screenElementInflater = (LayoutInflater)getSystemService(Context.LAYOUT_INFLATER_SERVICE);
        }
 
        public void addItem(final String item) {
            screenElementsData.add(item);
            notifyDataSetChanged();
        }

 
        @Override
        public int getItemViewType(int position) {
            return screenElementsData.get(position) == "1" ? ELEMENT_UPLOAD : ELEMENT_TEXT;
        }
 
        @Override
        public int getViewTypeCount() {
            return TYPE_MAX_COUNT;
        }
 
        @Override
        public int getCount() {
            return screenElementsData.size();
        }
 
        @Override
        public String getItem(int position) {
            return screenElementsData.get(position);
        }
 
        @Override
        public long getItemId(int position) {
            return position;
        }
 
        @Override
        public View getView(int position, View convertView, ViewGroup parent) {
            ViewHolder holder = null;
            int type = getItemViewType(position);
            System.out.println("getView " + position + " " + convertView + " type = " + type);
            if (convertView == null) {
                holder = new ViewHolder();
                switch (type) {
                    case ELEMENT_TEXT:
                        convertView = screenElementInflater.inflate(R.layout.element_text, null);
                        holder.textView = (TextView) convertView.findViewById(R.id.caption);
                        break;
                    case ELEMENT_UPLOAD:
                        convertView = screenElementInflater.inflate(R.layout.element_upload, null);
                        holder.textView = (TextView) convertView.findViewById(R.id.caption);
                        break;
                }
                convertView.setTag(holder);
            } else {
                holder = (ViewHolder)convertView.getTag();
            }
            holder.textView.setText(screenElementsData.get(position));
            return convertView;
        }
 
    }
 
    public static class ViewHolder {
        public TextView textView;
    }

}
