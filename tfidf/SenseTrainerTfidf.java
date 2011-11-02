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
	public int max_file;
	public int test_file;
	public SenseTrainerTfidf(){
		TokenizerFactory tokenizerFactory = IndoEuropeanTokenizerFactory.INSTANCE;
        this.tfIdf = new TfIdfDistance(tokenizerFactory);
        this.corpus_path = new ArrayList<String>();
        this.max_file = 1000;
        this.test_file = 100;
	}
	public SenseTrainerTfidf(int max_file, int test_file){
		TokenizerFactory tokenizerFactory = IndoEuropeanTokenizerFactory.INSTANCE;
        this.tfIdf = new TfIdfDistance(tokenizerFactory);
        this.corpus_path = new ArrayList<String>();
        this.max_file = max_file;
        this.test_file = test_file;
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
		//int right = 0;
		int right[] = new int[4];
		for (int i = 0;i<4;i++){
			right[i] = 0;
		}
		for (int i= 0; i < this.corpus_path.size();i++){
			File classDir = new File(this.corpus_path.get(i));
			if (!classDir.isDirectory()) {
				String msg = "Could not find training directory=" + classDir;
				System.out.println(msg); // in case exception gets lost in
											// shell
				continue;
			}
			
			String[] trainingFiles = classDir.list();
			if (this.max_file + this.test_file > trainingFiles.length){
				String msg = "not enought test doc:" + classDir;
				System.out.println(msg);
				msg = "num of doc:"+ trainingFiles.length;
				System.out.println(msg);
				continue;
			}
			double sim[];
			for (int j = this.max_file; j < this.max_file + this.test_file; ++j) {
				File file = new File(classDir, trainingFiles[j]);
				// Here, we can speed up a little bit by reading the file directly.
				// But now, I just reuse the TestWithAllSense function.
				sim = this.TestWithAllSense(file.getAbsolutePath());
				total +=1;
				int ret = this.RightClassify(i, sim);
				right[ret] +=1;
			}
		}
		//System.out.println("total" + total +"\tright" + right);
		int rightSum = 0;
		for (int i= 1;i<4;i++){
			System.out.println("total:" + total +"\tright."+i+":" + right[i]);
			rightSum += right[i];
		}
		if (total == 0){
			return -1; // no testing result
		}
		double p = (double) rightSum / (double) total ; // precision
		return p;
	}
	public int RightClassify(int corpus_i, double simScore[]){
		// this function will find the top three most similar sense.
		// if corpus_i is the top one, it will return 1;
		// if top two, it will return 2;
		// if top three, it will return 3;
		// else return 0;
		double maxThree[] = new double[3];
		int maxThreeIndex[] = new int[3];
		for (int i= 0;i<3;i++){
			maxThree[i] = 0.0;
			maxThreeIndex[i] = -1;
		}
		
		for (int i = 0; i < simScore.length;i++){
			for (int j = 0;j<3;j++){
				if (simScore[i] > maxThree[j]){
					//do insert
					this.insert(j, simScore[i], i, maxThree, maxThreeIndex);
					break;
				}
			}
		}
		
		for (int i = 0;i<3;i++){
			if (maxThreeIndex[i] == corpus_i){// catch
				return i+1;
			}
		}
		return 0;
	}
	private void insert(int order, double value, int index,  double maxValue[], int maxIndex[]){
		if (order == 2){
			//insert directly
			maxValue[2] = value;
			maxIndex[2] = index;
		}else if(order ==1 || order ==0){
			//move data
			for (int i = 1; i >= order;i--){
				maxValue[i+1] = maxValue[i];
				maxIndex[i+1] = maxIndex[i];
			}
			//insert
			maxValue[order] = value;
			maxIndex[order] = index;
		}
		return ;
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
		int tmp_max = this.max_file;
		if (tmp_max > trainingFiles.length){
			tmp_max = trainingFiles.length;
		}
		for (int j = 0; j < tmp_max; ++j) {
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
