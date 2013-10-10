package ca.formulize.android.data;

import android.os.Parcel;
import android.os.Parcelable;

/**
 * This object stores the connection information that the application needs to
 * connect to a Formulize server.
 * 
 * @author timch326
 * 
 */
public class ConnectionInfo implements Parcelable {

	private String connectionURL;
	private String connectionName;
	private String username;
	private String password;

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
		return connectionURL;
	}

	public void setConnectionURL(String mConnectionURL) {
		this.connectionURL = mConnectionURL;
	}

	public String getConnectionName() {
		return connectionName;
	}

	public void setConnectionName(String mConnectionName) {
		this.connectionName = mConnectionName;
	}

	public String getUsername() {
		return username;
	}

	public void setUsername(String mUsername) {
		this.username = mUsername;
	}

	public String getPassword() {
		return password;
	}

	public void setPassword(String mPassword) {
		this.password = mPassword;
	}

	@Override
	public int describeContents() {
		return 0;
	}

	@Override
	public String toString() {
		return "Name: " + connectionName + ", URL: " + connectionURL
				+ ", Username: " + username + ", Password: " + password;
	}

	@Override
	public void writeToParcel(Parcel dest, int flags) {
		dest.writeStringArray(new String[] { this.connectionURL,
				this.connectionName, this.username, this.password });
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
		connectionURL = pc.readString();
		connectionName = pc.readString();
		username = pc.readString();
		password = pc.readString();
	}
}
