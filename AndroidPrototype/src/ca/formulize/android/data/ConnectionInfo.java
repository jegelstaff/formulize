package ca.formulize.android.data;

import android.os.Parcel;
import android.os.Parcelable;

/**
 * This object stores the connection information that the application needs to
 * connect to a Formulize server.
 * @author timch326
 * 
 */
public class ConnectionInfo implements Parcelable {

	private String mConnectionURL;
	private String mConnectionName;
	private String mUsername;
	private String mPassword;

	public ConnectionInfo(String connectionURL, String connectionName) {
		this(connectionURL, connectionName, null, null);
	}

	public ConnectionInfo(String connectionURL, String connectionName,
			String username, String password) {
		this.setConnectionURL(connectionURL);
		this.setConnectionName(connectionName);
		this.setUsername(username);
		this.setPassword(password);
	}

	public String getConnectionURL() {
		return mConnectionURL;
	}

	public void setConnectionURL(String mConnectionURL) {
		this.mConnectionURL = mConnectionURL;
	}

	public String getConnectionName() {
		return mConnectionName;
	}

	public void setConnectionName(String mConnectionName) {
		this.mConnectionName = mConnectionName;
	}

	public String getUsername() {
		return mUsername;
	}

	public void setUsername(String mUsername) {
		this.mUsername = mUsername;
	}

	public String getPassword() {
		return mPassword;
	}

	public void setPassword(String mPassword) {
		this.mPassword = mPassword;
	}

	@Override
	public int describeContents() {
		return 0;
	}

	@Override
	public void writeToParcel(Parcel dest, int flags) {
		dest.writeStringArray(new String[] { this.mConnectionURL,
				this.mConnectionName, this.mUsername, this.mPassword });
	}

	// static field used to regenerate connection info from a parcel
	public static final Parcelable.Creator<ConnectionInfo> CREATOR = new Parcelable.Creator<ConnectionInfo>() {
		public ConnectionInfo createFromParcel(Parcel in) {
			return new ConnectionInfo(in);
		}

		@Override
		public ConnectionInfo[] newArray(int size) {
			return new ConnectionInfo[size];
		}
	};

	// Constructor used by CREATOR object to read data from parcel
	public ConnectionInfo(Parcel pc) {
		mConnectionURL = pc.readString();
		mConnectionName = pc.readString();
		mUsername = pc.readString();
		mPassword = pc.readString();
	}
}
