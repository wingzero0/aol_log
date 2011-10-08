import java.io.File;
import java.io.FileNotFoundException;
import java.io.FileOutputStream;
import java.io.IOException;
import java.util.Map;


public class TfIdfDistanceDemo {


	public static void main(String[] args) {
		SenseTrainerTfidf train = new SenseTrainerTfidf();

		Map<String, String> para = kit_lib.ParameterParser(args);

		if (!para.containsKey("model")){
			para.put("model", "trani_data/");
		}
		if (!para.containsKey("test")){
			para.put("test", "test_url/");
		}
		if (!para.containsKey("o")){
			para.put("o", "output.txt");
		}
		File dataDir = new File(para.get("model"));
		if (!dataDir.isDirectory()) {
			String msg = "-model is not a path:" + dataDir;
			System.out.println(msg); // in case exception gets lost in
			// shell
			return;
		}

		String[] trainingClassDir = dataDir.list();
		int counter = 0;
		for (int i = 0; i < trainingClassDir.length; ++i) {
			File classDir = new File(dataDir, trainingClassDir[i]);
			if (classDir.isDirectory()) {
				train.AddCorpusPath(classDir.getAbsolutePath());
				counter++;
			}
		}
		train.StartTraining();
		/*
		for (int i= 0; i < counter;i++){
			String content = train.CreateBigStr(i);
			//System.out.println(content);
			train.Train(content);
		}*/
		for (int i= 0; i < counter;i++){
			String content = train.CreateBigStr(i);
			System.out.println(train.Sim(content, "php doc"));
		}
		
		File testDir = new File(para.get("test"));
		if (!testDir.isDirectory()) {
			String msg = "-test is not a path" + testDir;
			System.out.println(msg); // in case exception gets lost in
			// shell
			return;
		}
		
		try {
			FileOutputStream newOut = new FileOutputStream(para.get("o"));
			String[] testClassDir = testDir.list();
			double[] simScore;
			for (int i = 0; i < testClassDir.length; ++i) {
				File html = new File(testDir, testClassDir[i]);
				if (html.isFile()) {
					simScore = train.TestWithAllSense(html.getAbsolutePath());
					for (int j = 0; j< simScore.length;j++){
						String output = "sense "+ j + ":" + testClassDir[i].toString() + "\t" + simScore[j] + "\n";
						System.out.println(output);
						newOut.write(output.getBytes());
						
					}
				}
			}
		} catch (FileNotFoundException e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
			return;
		} catch (IOException e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
		} 
		return;
	}
}
