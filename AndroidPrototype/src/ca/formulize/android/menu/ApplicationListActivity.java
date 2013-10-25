package ca.formulize.android.menu;

import java.util.ArrayList;

import com.example.formulizeprototype.R;
import com.example.formulizeprototype.R.id;
import com.example.formulizeprototype.R.layout;
import com.example.formulizeprototype.R.menu;

import android.app.Activity;
import android.content.Intent;
import android.os.Bundle;
import android.util.Log;
import android.view.Menu;
import android.view.View;
import android.widget.AdapterView;
import android.widget.AdapterView.OnItemClickListener;
import android.widget.ArrayAdapter;
import android.widget.ListView;
import android.widget.TextView;
import ca.formulize.android.connection.FUserSession;
import ca.formulize.android.data.ConnectionInfo;


public class ApplicationListActivity extends Activity {

	public static final String APPLICATION = "Application";

	@Override
	protected void onCreate(Bundle savedInstanceState) {
		super.onCreate(savedInstanceState);
		setContentView(R.layout.activity_application_list);
		
        ArrayList<String> applicationList = getUserApplications();
        ArrayAdapter<String> arrayAdapter =      
        new ArrayAdapter<String>(this,android.R.layout.simple_list_item_1, applicationList);
        
		ListView applicationListView = (ListView) findViewById(R.id.applicationList);
        applicationListView.setAdapter(arrayAdapter); 
        applicationListView.setOnItemClickListener(new ApplicationListClickListener());
        
        ConnectionInfo info = FUserSession.getInstance().getConnectionInfo();
        Log.d("Formulize", info.toString());
	}

	@Override
	public boolean onCreateOptionsMenu(Menu menu) {
		// Inflate the menu; this adds items to the action bar if it is present.
		getMenuInflater().inflate(R.menu.application_list, menu);
		return true;
	}
	
	// Hard coded for prototyping
	private ArrayList<String> getUserApplications() {
		ArrayList<String> applicationList = new ArrayList<String>();
		applicationList.add("Wildlife Monitoring");
		applicationList.add("Zoo Animal Caretaking");
		return applicationList;
	}
	
	private class ApplicationListClickListener implements OnItemClickListener {

		@Override
		public void onItemClick(AdapterView<?> parent, View view, int position,
				long applicationID) {

			// Go to Application Screen List Activity with selected application
			TextView applicationName = (TextView) view.findViewById(android.R.id.text1);
			Intent screenListIntent = new Intent(ApplicationListActivity.this,
					ScreenListActivity.class);
			screenListIntent.putExtra(APPLICATION, applicationName.getText().toString());
			startActivity(screenListIntent);
			
		}
	}

}
