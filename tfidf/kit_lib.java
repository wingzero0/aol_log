import java.util.Map;
import java.util.TreeMap;
import java.util.regex.Matcher;
import java.util.regex.Pattern;



public class kit_lib {
	public static Map<String, String> ParameterParser(String args[]){
		Map<String, String> para = new TreeMap<String, String>();
		String regex = "^-(.*)";    // Regular expression string.
		Pattern p = Pattern.compile(regex); // Compiles regular expression into Pattern.
		for (int i = 0; i < args.length; i++){
			//System.err.println(args[i]);
		    Matcher m = p.matcher(args[i]); // Creates Matcher with subject s and Pattern p.
		    if (m.find()){
		    //String paraStr = m.group();
		    //if (paraStr!=null){
		    	para.put(m.group(1), args[i+1]);
				i++;
		    //}
		    }
		}
		return para;
	}
}
