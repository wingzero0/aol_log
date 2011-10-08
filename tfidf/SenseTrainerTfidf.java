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
	public int StartTraining(){
		for (int i= 0; i < this.corpus_path.size();i++){
			String sense = this.CreateBigStr(i);
			//System.out.println(content);
			this.Train(sense);
		}
		return 0; 
	}
	public String CreateBigStr(int ith_path){
		StringBuffer BigStr = new StringBuffer();
		File classDir = new File(this.corpus_path.get(ith_path));
		if (!classDir.isDirectory()) {
			String msg = "Could not find training directory=" + classDir;
			System.out.println(msg); // in case exception gets lost in
										// shell
			return null;
		}
		
		String[] trainingFiles = classDir.list();
		for (int j = 0; j < trainingFiles.length; ++j) {
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
