package ca.formulize.android.connection;

import android.app.AlertDialog;
import android.app.Dialog;
import android.content.DialogInterface;
import android.os.Bundle;
import android.support.v4.app.DialogFragment;
import android.view.LayoutInflater;
import android.view.View;
import android.widget.EditText;
import ca.formulize.android.data.ConnectionInfo;

import com.example.formulizeprototype.R;

/**
 * The login dialogue that should appear when the user attempts to connect to a
 * server without user credentials
 * 
 * @author timch326
 * 
 */
public class UserLoginDialogFragment extends DialogFragment {

	public static final String EXTRA_CONNECITON_INFO = "ca.formulize.android.extras.connectionInfo";

	// Connection Details
	private ConnectionInfo connectionInfo;
	private String username;
	private String password;

	// UI References
	private EditText usernameView;
	private EditText passwordView;

	public Dialog onCreateDialog(Bundle savedInstanceState) {
		AlertDialog.Builder builder = new AlertDialog.Builder(getActivity());

		// Get the layout inflater
		LayoutInflater inflater = getActivity().getLayoutInflater();
		View view = inflater.inflate(R.layout.dialog_login, null);

		// Set UI References
		usernameView = (EditText) view.findViewById(R.id.username);
		passwordView = (EditText) view.findViewById(R.id.password);

		builder.setView(view)
				.setPositiveButton(android.R.string.ok,
						new DialogInterface.OnClickListener() {

							@Override
							public void onClick(DialogInterface dialog,
									int which) {
								// TODO: Validate Inputs
								username = usernameView.getText().toString();
								password = passwordView.getText().toString();
								connectionInfo.setUsername(username);
								connectionInfo.setPassword(password);

								FUserSession session = FUserSession
										.getInstance();
								session.createConnection(getActivity(),
										connectionInfo);
							}
						})
				.setNegativeButton(android.R.string.cancel,
						new DialogInterface.OnClickListener() {

							@Override
							public void onClick(DialogInterface dialog,
									int which) {
								UserLoginDialogFragment.this.getDialog()
										.cancel();

							}
						}).setTitle(R.string.sign_in_label);

		// Retrieve arguments
		Bundle args = getArguments();
		connectionInfo = (ConnectionInfo) args
				.getParcelable(EXTRA_CONNECITON_INFO);

		return builder.create();
	}
}
