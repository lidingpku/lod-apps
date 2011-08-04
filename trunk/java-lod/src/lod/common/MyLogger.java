package lod.common;

import java.io.File;

import sw4j.util.Sw4jException;
import sw4j.util.ToolIO;

public class MyLogger {
	static File logFile= MyConfig.getFile(MyConfig.DIV_FILE_LOG); 
	
	public static void reset(){
		try {
			ToolIO.pipeStringToFile("", logFile, false, false);
		} catch (Sw4jException e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
		}
	}
	
	public static void print(String data){
		try {
			ToolIO.pipeStringToFile(data, logFile, false, true);
		} catch (Sw4jException e) {
			e.printStackTrace();
		}
		System.out.print(data);
	}

	public static void print(int data){
		try {
			ToolIO.pipeStringToFile(data+"", logFile, false, true);
		} catch (Sw4jException e) {
			e.printStackTrace();
		}
		System.out.print(data);
	}
	
	public static void println(int data){
		try {
			ToolIO.pipeStringToFile(data+"\n", logFile, false, true);
		} catch (Sw4jException e) {
			e.printStackTrace();
		}
		System.out.println(data);
	}
	
	public static void println(Object data){
		try {
			ToolIO.pipeStringToFile(data.toString()+"\n", logFile, false, true);
		} catch (Sw4jException e) {
			e.printStackTrace();
		}
		System.out.println(data);
	}
}
