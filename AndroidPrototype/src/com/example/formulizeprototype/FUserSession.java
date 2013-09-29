package com.example.formulizeprototype;

public class FUserSession {
	private static FUserSession instance;
	private String username;
	private String fURL;
	
	public static FUserSession getInstance() {
		if(instance == null) {
			instance = new FUserSession();
			return instance;
		}
		else return instance;
	}
	private FUserSession() {
	}
	
	public String getUsername() {
		return username;
	}
	
	public String getFURL() {
		return fURL;
	}
	
	public void setUsername(String newUsername) {
		username = newUsername;
	}
	
	public void setFURL(String newFURL) {
		fURL = newFURL;
	}
}
