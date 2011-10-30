import java.io.File;
import java.io.IOException;
import java.util.ArrayList;
import com.aliasi.util.Files;

import com.aliasi.spell.TfIdfDistance;
import com.aliasi.tokenizer.IndoEuropeanTokenizerFactory;
import com.aliasi.tokenizer.TokenizerFactory;

public class SenseTrainerTfidf {
	private TfIdfDistance tfIdf;
	private ArrayList<String> corpus_path;
	private ArrayList<String> test_path;
	private static String ENCODING = "UTF-8";
	public SenseTrainerTfidf(){
		TokenizerFactory tokenizerFactory = IndoEuropeanTokenizerFactory.INSTANCE;
        this.tfIdf = new TfIdfDistance(tokenizerFactory);
        this.corpus_path = new ArrayList<String>();
        
	}
	public int AddCorpusPath(String path){
		this.corpus_path.add(path);
		return 0;
	}
	public int Train(String new_sense){
		this.tfIdf.handle(new_sense);
		return 0;
	}
	public double Sim(String sense, String test_doc){
		double sim = this.tfIdf.proximity(sense, test_doc);
		//System.err.println(tfIdf.docFrequency(test_doc));
		//System.err.println(tfIdf.idf(test_doc));
		return sim;
	}
	public double[] TestWithAllSense(String test_doc_path){
		File file = new File(test_doc_path);
		String text = new String();
		try {
			text = Files.readFromFile(file, ENCODING);
		} catch (IOException e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
		}
		double simScore[] = new double[corpus_path.size()];
		for (int i= 0; i < this.corpus_path.size();i++){
			String sense = this.CreateBigStr(i);
			simScore[i] = this.Sim(sense, text);
		}
		return simScore;
	}
	public double SelfTest(){
		int total = 0;
		int right = 0;
		for (int i= 0; i < this.corpus_path.size();i++){
			File classDir = new File(this.corpus_path.get(i));
			if (!classDir.isDirectory()) {
				String msg = "Could not find training directory=" + classDir;
				System.out.println(msg); // in case exception gets lost in
											// shell
				continue;
			}
			
			String[] trainingFiles = classDir.list();
			int max_file = 1000;
			int test_file = 100;
			if (max_file + test_file > trainingFiles.length){
				String msg = "not enought test doc:" + classDir;
				System.out.println(msg);
				msg = "num of doc:"+ trainingFiles.length;
				System.out.println(msg);
				continue;
			}
			double sim[];
			for (int j = max_file; j < max_file + test_file; ++j) {
				File file = new File(classDir, trainingFiles[j]);
				// Here, we can speed up a little bit by reading the file directly.
				// But now, I just reuse the TestWithAllSense function.
				sim = this.TestWithAllSense(file.getAbsolutePath());
				total +=1;
				if (this.RightClassify(i, sim) == true){
					right +=0;
				}
			}
		}
		if (total == 0){
			return -1; // no testing result
		}
		double p = (double) right / (double) total ; // precision
		return p;
	}
	public boolean RightClassify(int corpus_i, double simScore[]){
		double maxValue = 0.0;
		int maxIndex = -1;
		for (int i = 0; i < simScore.length;i++){
			if (simScore[i] > maxValue){
				maxIndex = i;
				maxValue = simScore[i];
			}
		}
		if (maxIndex == corpus_i){
			return true;
		}else{
			return false;
		}
	}
	public int StartTraining(){
		for (int i= 0; i < this.corpus_path.size();i++){
			String sense = this.CreateBigStr(i);
			//System.out.println(content);
			this.Train(sense);
		}
		return 0; 
	}
	public String[] getAllSenseName(){
		String[] names = new String[this.corpus_path.size()];
		for (int i = 0;i< this.corpus_path.size();i++){
			names[i] = this.getSenseName(i);
		}
		return names;
	}
	public String getSenseName(int ith_path){
		File classDir = new File(this.corpus_path.get(ith_path));
		return classDir.getName();
	}
	public String CreateBigStr(int ith_path){
		// each class just use it's first 100 document to make the trainning content
		StringBuffer BigStr = new StringBuffer();
		File classDir = new File(this.corpus_path.get(ith_path));
		if (!classDir.isDirectory()) {
			String msg = "Could not find training directory=" + classDir;
			System.out.println(msg); // in case exception gets lost in
										// shell
			return null;
		}
		
		String[] trainingFiles = classDir.list();
		int max_file = 1000;
		if (max_file > trainingFiles.length){
			max_file = trainingFiles.length;
		}
		for (int j = 0; j < max_file; ++j) {
			File file = new File(classDir, trainingFiles[j]);
			
			String text = new String();
			try {
				text = Files.readFromFile(file, ENCODING);
			} catch (IOException e) {
				// TODO Auto-generated catch block
				e.printStackTrace();
			}
			BigStr.append(text);
		}
		
		return BigStr.toString();
	}
}
