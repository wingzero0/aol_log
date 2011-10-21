// this class want to calculate the kendall taub in two ranking
// sample use: java KendallTaub ranking1.txt ranking2.txt
// The fromat of ranking*.txt show as below:
// query1 \t url11 \t score
// query1 \t url12 \t score
// ...
// query2 \n url21 \t score
// ...
// it the query or url appears in one ranking file but not in other one,
// I just ignore that record.

import java.io.BufferedReader;
import java.io.DataInputStream;
import java.io.FileInputStream;
import java.io.InputStreamReader;
import java.util.ArrayList;
import java.util.Iterator;
import java.util.Map;
import java.util.Map.Entry;
import java.util.TreeMap;

import it.unimi.dsi.law.stat.KendallTau;

public class KendallTaub {
	public ArrayList<Double> tau;
	public TreeMap<String, TreeMap<String, Double> > rank1;
	public TreeMap<String, TreeMap<String, Double> > rank2;
	public KendallTaub(){
		this.rank1 = new TreeMap<String, TreeMap<String, Double> >();
		this.rank2 = new TreeMap<String, TreeMap<String, Double> >();
		this.tau = new ArrayList<Double>();
	}
	public int AddRankQUPair(int rank, String q, String u, Double score){
		TreeMap<String, TreeMap<String, Double> > add;
		if (rank == 1){
			add = rank1;
		}else{
			add = rank2;
		}
		
		// set the score to <q,u> index
		TreeMap<String,Double> tmp;
		if (!add.containsKey(q)){
			tmp = new TreeMap<String,Double>();
			add.put(q, tmp);
		}
		tmp = add.get(q);
		if (!tmp.containsKey(u)){
			//Double s = new Double(score);
			tmp.put(u, score);
		}else{
			System.err.println("duplicate <q,u> pair: "+ q + " " + u);
			return -1;
		}
		return 0;
	}
	public int AddRankQUPair(int rank, String line){
		String[] record = line.split("\t");
		if (record.length != 3){
			System.err.println ("error format: " + line);
		}
		Double score = Double.valueOf(record[2]);
		return this.AddRankQUPair(rank, record[0], record[1], score);
	}
	public double eval(){
		// eval the kendall of two ranking by Kendall lib
		// convert each two corresponding query results to two double array.
		
		//find <q,u> pair
		Iterator<Entry<String, TreeMap<String, Double> > > it1 = rank1.entrySet().iterator();
	    while (it1.hasNext()) {
	    	// get query
	        Map.Entry pairs1 = (Map.Entry)it1.next();
	        String query = (String) pairs1.getKey();
	        if (!rank2.containsKey(query)){// check whether the query is in two ranking;
	        	System.err.println("skip query:"+query);
	        	continue;//skip it
	        }
	        
	        // get url
	        TreeMap<String, Double> rank1_urls = (TreeMap<String,Double>) pairs1.getValue(); // get the inner loop obj
	        TreeMap<String, Double> rank2_urls = rank2.get(query); // get the inner loop obj
	        Iterator<Entry<String, Double> > it2 = rank1_urls.entrySet().iterator();
	        
	        
	        ArrayList<Double> v1 = new ArrayList<Double>();
			ArrayList<Double> v2 = new ArrayList<Double>();
			
	        while (it2.hasNext()) {
	            Map.Entry pairs2 = (Map.Entry)it2.next();
	            String url = (String)pairs2.getKey();
	            if (!rank2_urls.containsKey(url)){// check whether the url is in two ranking;
	            	System.err.println("skip url:"+url);
	            	continue;
	            }
	            v1.add(rank1_urls.get(url));
	            v2.add(rank2_urls.get(url));
	        }
	        if (v1.size()!=0){ // all the <q, u> pairs with same query in rank1 negither appears in rank2
	        	double ret = this._eval(v1, v2);
	        	if (ret == -2.0){
	        		System.err.println("array size erro");
	        	}else{
	        		this.tau.add(new Double(ret));
	        	}
	        }
	    }
	    
	    double sum = 0.0;
	    for (int i= 0;i<this.tau.size();i++){
	    	sum+=this.tau.get(i);
	    	//System.err.println(this.tau.get(i));
	    }
	    
	    //double avg = 0.0;
	    double avg = sum / (double) this.tau.size();
	    return avg;
	}
	
	public double _eval(ArrayList<Double> v1,ArrayList<Double> v2){
		if (v1.size() != v2.size()){
			return -2.0; // error
		}
		double[] v1_array = new double[v1.size()];
	    for (int i= 0;i<v1.size();i++){
	    	v1_array[i] = v1.get(i).doubleValue();
	    }
	    double[] v2_array = new double[v2.size()];
	    for (int i= 0;i<v2.size();i++){
	    	v2_array[i] = v2.get(i).doubleValue();
	    }
	    double ret = 0.0;
	    ret = KendallTau.compute(v1_array, v2_array);
	    
		return ret;
	}
	public int DumpRank(TreeMap<String, TreeMap<String, Double> > rank){
		Iterator<Entry<String, TreeMap<String, Double> > > it = rank.entrySet().iterator();
	    while (it.hasNext()) {
	        Map.Entry pairs = (Map.Entry)it.next();
	        TreeMap<String, Double> tmp =(TreeMap<String,Double>) pairs.getValue(); // get the inner loop obj
	        
	        Iterator<Entry<String, Double> > it2 = tmp.entrySet().iterator();
	        while (it2.hasNext()) {
	            Map.Entry pairs2 = (Map.Entry)it2.next();
	            System.out.println(pairs.getKey() + "\t" + pairs2.getKey() + "\t" + pairs2.getValue());
	        }
	        //System.out.println(pairs.getKey() + " = " + pairs.getValue());
	    }
		return 0;
	}
	public int ReadRankFromFile(int rank, String filename){
		try {
			FileInputStream fstream = new FileInputStream(filename);
			// Get the object of DataInputStream
			DataInputStream in = new DataInputStream(fstream);
			BufferedReader br = new BufferedReader(new InputStreamReader(in));
			String strLine;
			//Read File Line By Line
			while ((strLine = br.readLine()) != null)   {
				// Print the content on the console
				this.AddRankQUPair(rank, strLine);
			}
			//Close the input stream
			in.close();
		} catch (Exception e) {
			System.err.println("Error: " + e.getMessage());
		}
		
		return 0;
	}
	public static void main(String[] args) {
		try{
			Map<String, String> para = kit_lib.ParameterParser(args);
			if (!para.containsKey("rank1")){
				System.err.println ("please specify the ranking file with \"-rank1\" option");
				return;
			}
			if (!para.containsKey("rank2")){
				System.err.println ("please specify the ranking file with \"-rank2\" option");
				return;
			}
			KendallTaub k = new KendallTaub();
			k.ReadRankFromFile(1, para.get("rank1"));
			k.ReadRankFromFile(2, para.get("rank2"));
			//k.DumpRank(k.rank2);
			double ret = 0.0;
			ret = k.eval();
			System.out.println(ret);
		}catch (Exception e){//Catch exception if any
			System.err.println("Error: outer " + e.getMessage());
		}
	}
}
