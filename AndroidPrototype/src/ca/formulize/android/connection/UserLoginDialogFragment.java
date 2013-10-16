package ca.formulize.android.connection;

import android.app.AlertDialog;
import android.app.Dialog;
import android.content.DialogInterface;
import android.os.Bundle;
import android.support.v4.app.DialogFragment;
import android.view.LayoutInflater;
import android.view.View;
import android.widget.EditText;
import android.widget.TextView;
import ca.formulize.android.data.ConnectionInfo;

import com.example.formulizeprototype.R;

/**
 * The login dialogue that should appear when the user attempts to connect to a
 * server without user credentials.
 * 
 * @author timch326
 * 
 */
public class UserLoginDialogFragment extends DialogFragment {

	public static final String EXTRA_CONNECITON_INFO = "ca.formulize.android.extras.connectionInfo";
	public static final String EXTRA_IS_REATTEMPT = "ca.formulize.android.extras.isReattempt";

	// Connection Details
	private ConnectionInfo connectionInfo;
	private Boolean isReattempt;
	private String username;
	private String password;

	// UI References
	private TextView errorMessageView;
	private EditText usernameView;
	private EditText passwordView;

	public Dialog onCreateDialog(Bundle savedInstanceState) {
		AlertDialog.Builder builder = new AlertDialog.Builder(getActivity());

		// Get the layout inflater
		LayoutInflater inflater = getActivity().getLayoutInflater();
		View view = inflater.inflate(R.layout.dialog_login, null);

		// Set UI References
		errorMessageView = (TextView) view.findViewById(R.id.errorMessage);
		usernameView = (EditText) view.findViewById(R.id.username);
		passwordView = (EditText) view.findViewById(R.id.password);
		
		// Retrieve arguments
		Bundle args = getArguments();
		connectionInfo = (ConnectionInfo) args
				.getParcelable(EXTRA_CONNECITON_INFO);
		isReattempt = args.getBoolean(EXTRA_IS_REATTEMPT, false);
		
		// Show and set error message if this is a login re-attempt
		if (isReattempt) {
			errorMessageView.setText(R.string.reattempt_message);
			errorMessageView.setVisibility(View.VISIBLE);
		}

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

		return builder.create();
	}
}
